<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class PaymentAfterRevisionSettlementFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_can_pay_only_upward_delta_after_active_closed_paid_revision(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidServiceOnlyNote();

        $revision = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-payment-after-revision-001',
            array_replace($this->upwardRevisionPayload(), ['base_revision_id' => $this->revisionBaseForTest('note-payment-after-revision-001')]),
            'admin-payment-after-revision-001',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $currentWorkItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-payment-after-revision-001')
            ->where('id', '<>', 'wi-payment-after-revision-old-001')
            ->value('id');

        self::assertNotSame('', $currentWorkItemId);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'payment-payment-after-revision-001',
            'note_id' => 'note-payment-after-revision-001',
            'work_item_id' => $currentWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $currentWorkItemId,
            'allocated_amount_rupiah' => 100000,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-payment-after-revision-001']))
            ->post(route('admin.notes.payments.store', ['noteId' => 'note-payment-after-revision-001']), [
                'selected_row_ids' => [$currentWorkItemId . '::service_fee::' . $currentWorkItemId],
                'payment_method' => 'cash',
                'paid_at' => '2026-05-22',
                'amount_received' => 20000,
            ])
            ->assertRedirect(route('admin.notes.show', ['noteId' => 'note-payment-after-revision-001']))
            ->assertSessionHasNoErrors();

        $newPaymentId = (string) DB::table('customer_payments')
            ->where('id', '<>', 'payment-payment-after-revision-001')
            ->value('id');

        self::assertNotSame('', $newPaymentId);

        $this->assertDatabaseHas('customer_payments', [
            'id' => $newPaymentId,
            'amount_rupiah' => 20000,
            'paid_at' => '2026-05-22',
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $newPaymentId,
            'amount_paid_rupiah' => 20000,
            'amount_received_rupiah' => 20000,
            'change_rupiah' => 0,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $newPaymentId,
            'note_id' => 'note-payment-after-revision-001',
            'work_item_id' => $currentWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $currentWorkItemId,
            'allocated_amount_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'note-payment-after-revision-001',
            'total_rupiah' => 120000,
            'allocated_rupiah' => 120000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 120000,
            'outstanding_rupiah' => 0,
        ]);
    }

    public function test_admin_transfer_payment_after_upward_revision_records_only_delta_without_cash_detail(): void
    {
        $this->seedClosedPaidServiceOnlyNote();

        $revision = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-payment-after-revision-001',
            array_replace($this->upwardRevisionPayload(), ['base_revision_id' => $this->revisionBaseForTest('note-payment-after-revision-001')]),
            'admin-payment-after-revision-001',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $currentWorkItemId = (string) DB::table('work_items')
            ->where('note_id', 'note-payment-after-revision-001')
            ->where('id', '<>', 'wi-payment-after-revision-old-001')
            ->value('id');

        self::assertNotSame('', $currentWorkItemId);

        $beforeLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');

        self::assertSame([
            'total_in_rupiah' => 100000,
            'cash_in_rupiah' => 0,
            'transfer_in_rupiah' => 0,
            'total_out_rupiah' => 0,
        ], $beforeLedger);

        $payment = app(RecordAndAllocateNotePaymentHandler::class)->handle(
            'note-payment-after-revision-001',
            20000,
            '2026-05-22',
            [$currentWorkItemId . '::service_fee::' . $currentWorkItemId],
            'transfer',
            null,
        );

        self::assertTrue($payment->isSuccess(), $payment->message());

        $newPaymentId = (string) DB::table('customer_payments')
            ->where('id', '<>', 'payment-payment-after-revision-001')
            ->value('id');

        self::assertNotSame('', $newPaymentId);

        $this->assertDatabaseHas('customer_payments', [
            'id' => $newPaymentId,
            'amount_rupiah' => 20000,
            'paid_at' => '2026-05-22',
            'payment_method' => 'transfer',
        ]);

        $this->assertDatabaseMissing('customer_payment_cash_details', [
            'customer_payment_id' => $newPaymentId,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $newPaymentId,
            'note_id' => 'note-payment-after-revision-001',
            'work_item_id' => $currentWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $currentWorkItemId,
            'allocated_amount_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'note-payment-after-revision-001',
            'total_rupiah' => 120000,
            'allocated_rupiah' => 120000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 120000,
            'outstanding_rupiah' => 0,
        ]);

        $afterLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');

        self::assertSame([
            'total_in_rupiah' => 120000,
            'cash_in_rupiah' => 0,
            'transfer_in_rupiah' => 20000,
            'total_out_rupiah' => 0,
        ], $afterLedger);
    }

    private function seedClosedPaidServiceOnlyNote(): void
    {
        $this->seedNoteBase(
            'note-payment-after-revision-001',
            'Budi Payment Revision Original',
            '2026-05-20',
            100000,
            'closed',
        );

        $this->seedWorkItemBase(
            'wi-payment-after-revision-old-001',
            'note-payment-after-revision-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-payment-after-revision-old-001',
            'Servis Payment Revision Original',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-payment-after-revision-001',
            'note-payment-after-revision-001-r001',
            'wi-payment-after-revision-old-001',
            'Budi Payment Revision Original',
            '2026-05-20',
            100000,
            'Servis Payment Revision Original',
            100000,
        );

        $this->seedCustomerPaymentBase(
            'payment-payment-after-revision-001',
            100000,
            '2026-05-20',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-payment-after-revision-001',
            'payment-payment-after-revision-001',
            'note-payment-after-revision-001',
            100000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-payment-after-revision-old-001',
            'customer_payment_id' => 'payment-payment-after-revision-001',
            'note_id' => 'note-payment-after-revision-001',
            'work_item_id' => 'wi-payment-after-revision-old-001',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-payment-after-revision-old-001',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
            'allocation_priority' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function upwardRevisionPayload(): array
    {
        return [
            'reason' => 'Payment after active revision should only pay delta.',
            'note' => [
                'customer_name' => 'Budi Payment Revision Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Payment Revision Revised',
                        'price_rupiah' => 120000,
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }
}
