<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RefundAfterRevisionCurrentRowBoundaryFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_refund_after_revision_rejects_stale_old_row_id_and_accepts_current_replacement_row_id(): void
    {
        $user = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidServiceOnlyNote();

        $revision = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-refund-revision-001',
            $this->revisionPayload(),
            'admin-test-001',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $this->assertDatabaseMissing('work_items', [
            'id' => 'wi-refund-revision-old-001',
            'note_id' => 'note-refund-revision-001',
        ]);

        $currentWorkItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-refund-revision-001')
            ->where('id', '<>', 'wi-refund-revision-old-001')
            ->value('id');

        self::assertNotSame('', $currentWorkItemId);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-refund-revision-001',
            'note_id' => 'note-refund-revision-001',
            'work_item_id' => $currentWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $currentWorkItemId,
            'allocated_amount_rupiah' => 100000,
        ]);

        $this->actingAs($user)
            ->from(route('admin.notes.show', ['noteId' => 'note-refund-revision-001']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-refund-revision-001']), [
                'selected_row_ids' => ['wi-refund-revision-old-001'],
                'refunded_at' => '2026-05-22',
                'reason' => 'Attempt stale historical row refund.',
            ])
            ->assertRedirect(route('admin.notes.show', ['noteId' => 'note-refund-revision-001']))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->actingAs($user)
            ->from(route('admin.notes.show', ['noteId' => 'note-refund-revision-001']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-refund-revision-001']), [
                'selected_row_ids' => [$currentWorkItemId],
                'refunded_at' => '2026-05-22',
                'reason' => 'Refund current replacement row.',
            ])
            ->assertRedirect(route('admin.notes.index'))
            ->assertSessionHas('success');

        $refundId = (string) DB::table('customer_refunds')
            ->where('note_id', 'note-refund-revision-001')
            ->value('id');

        self::assertNotSame('', $refundId);

        $this->assertDatabaseHas('customer_refunds', [
            'id' => $refundId,
            'customer_payment_id' => 'payment-refund-revision-001',
            'note_id' => 'note-refund-revision-001',
            'amount_rupiah' => 100000,
            'reason' => 'Refund current replacement row.',
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-refund-revision-001',
            'note_id' => 'note-refund-revision-001',
            'work_item_id' => $currentWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $currentWorkItemId,
            'refunded_amount_rupiah' => 100000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-refund-revision-old-001',
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $currentWorkItemId,
            'note_id' => 'note-refund-revision-001',
            'status' => WorkItem::STATUS_CANCELED,
        ]);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'note-refund-revision-001',
            'total_rupiah' => 0,
            'allocated_rupiah' => 100000,
            'refunded_rupiah' => 100000,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => 0,
        ]);
    }

    private function seedClosedPaidServiceOnlyNote(): void
    {
        $this->seedNoteBase(
            'note-refund-revision-001',
            'Budi Refund Revision Original',
            '2026-05-20',
            100000,
            'closed',
        );

        $this->seedWorkItemBase(
            'wi-refund-revision-old-001',
            'note-refund-revision-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-refund-revision-old-001',
            'Servis Refund Revision Original',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-refund-revision-001',
            'note-refund-revision-001-r001',
            'wi-refund-revision-old-001',
            'Budi Refund Revision Original',
            '2026-05-20',
            100000,
            'Servis Refund Revision Original',
            100000,
        );

        $this->seedCustomerPaymentBase(
            'payment-refund-revision-001',
            100000,
            '2026-05-20',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-refund-revision-001',
            'payment-refund-revision-001',
            'note-refund-revision-001',
            100000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-refund-revision-old-001',
            'customer_payment_id' => 'payment-refund-revision-001',
            'note_id' => 'note-refund-revision-001',
            'work_item_id' => 'wi-refund-revision-old-001',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-refund-revision-old-001',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function revisionPayload(): array
    {
        return [
            'reason' => 'Refund after revision current-row boundary characterization.',
            'note' => [
                'customer_name' => 'Budi Refund Revision Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Refund Revision Revised',
                        'price_rupiah' => 100000,
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }
}
