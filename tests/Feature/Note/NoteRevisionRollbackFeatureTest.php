<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Ports\Out\AuditLogPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteRevisionRollbackFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_revision_rolls_back_after_replacement_when_audit_fails(): void
    {
        $this->seedPartiallyPaidServiceOnlyNote();

        $this->app->instance(AuditLogPort::class, new class implements AuditLogPort {
            /** @param array<string, mixed> $context */
            public function record(string $event, array $context = []): void
            {
                if ($event === 'note_revision_created') {
                    throw new RuntimeException('forced revision audit failure');
                }
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('forced revision audit failure');

        try {
            $this->app->make(CreateNoteRevisionHandler::class)->handle(
                'note-rollback-001',
                $this->revisionPayload(),
                'admin-test-001',
                false,
            );
        } finally {
            $this->assertOriginalNoteStateWasRestored();
            $this->assertReplacementRowsWereRolledBack();
            $this->assertPaymentAllocationWasRestored();
            $this->assertRevisionArtifactsWereRolledBack();
            $this->assertProjectionWasRolledBack();
        }
    }

    private function seedPartiallyPaidServiceOnlyNote(): void
    {
        $this->seedNoteBase(
            'note-rollback-001',
            'Budi Rollback Original',
            '2026-05-20',
            100000,
            'open',
        );

        $this->seedWorkItemBase(
            'wi-rollback-001',
            'note-rollback-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-rollback-001',
            'Servis Rollback Original',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-rollback-001',
            'note-rollback-001-r001',
            'wi-rollback-001',
            'Budi Rollback Original',
            '2026-05-20',
            100000,
            'Servis Rollback Original',
            100000,
        );

        $this->seedCustomerPaymentBase(
            'payment-rollback-001',
            40000,
            '2026-05-20',
        );

        $this->seedPaymentAllocationBase(
            'payment-allocation-rollback-001',
            'payment-rollback-001',
            'note-rollback-001',
            40000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-rollback-001',
            'customer_payment_id' => 'payment-rollback-001',
            'note_id' => 'note-rollback-001',
            'work_item_id' => 'wi-rollback-001',
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => 'wi-rollback-001',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 40000,
            'allocation_priority' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function revisionPayload(): array
    {
        return [
            'reason' => 'Rollback characterization after replacement.',
            'note' => [
                'customer_name' => 'Budi Rollback Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => [
                        'name' => 'Servis Rollback Revised',
                        'price_rupiah' => 120000,
                        'notes' => null,
                    ],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }

    private function assertOriginalNoteStateWasRestored(): void
    {
        $this->assertDatabaseHas('notes', [
            'id' => 'note-rollback-001',
            'customer_name' => 'Budi Rollback Original',
            'customer_phone' => null,
            'transaction_date' => '2026-05-20',
            'total_rupiah' => 100000,
            'current_revision_id' => 'note-rollback-001-r001',
            'latest_revision_number' => 1,
        ]);

        $this->assertDatabaseMissing('notes', [
            'id' => 'note-rollback-001',
            'customer_name' => 'Budi Rollback Revised',
            'total_rupiah' => 120000,
        ]);
    }

    private function assertReplacementRowsWereRolledBack(): void
    {
        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-rollback-001',
            'note_id' => 'note-rollback-001',
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-rollback-001',
            'service_name' => 'Servis Rollback Original',
            'service_price_rupiah' => 100000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->assertDatabaseMissing('work_item_service_details', [
            'service_name' => 'Servis Rollback Revised',
            'service_price_rupiah' => 120000,
        ]);
    }

    private function assertPaymentAllocationWasRestored(): void
    {
        $this->assertDatabaseHas('payment_component_allocations', [
            'id' => 'pca-rollback-001',
            'customer_payment_id' => 'payment-rollback-001',
            'note_id' => 'note-rollback-001',
            'work_item_id' => 'wi-rollback-001',
            'component_ref_id' => 'wi-rollback-001',
            'allocated_amount_rupiah' => 40000,
        ]);

        self::assertSame(
            40000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-rollback-001')
                ->sum('allocated_amount_rupiah'),
        );
    }

    private function assertRevisionArtifactsWereRolledBack(): void
    {
        $this->assertDatabaseMissing('note_revisions', [
            'id' => 'note-rollback-001-r002',
            'note_root_id' => 'note-rollback-001',
        ]);

        $this->assertDatabaseMissing('note_revision_settlements', [
            'id' => 'note-rollback-001-r002-settlement',
            'note_root_id' => 'note-rollback-001',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'note_revision_created',
        ]);
    }

    private function assertProjectionWasRolledBack(): void
    {
        self::assertSame(
            0,
            DB::table('note_history_projection')
                ->where('note_id', 'note-rollback-001')
                ->count(),
        );
    }
}
