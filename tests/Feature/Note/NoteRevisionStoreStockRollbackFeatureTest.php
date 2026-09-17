<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\AuditLogPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteRevisionStoreStockRollbackFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_store_stock_revision_rolls_back_inventory_reversal_and_reissue_when_audit_fails(): void
    {
        $this->seedOpenServiceStoreStockNote();

        $this->app->instance(AuditLogPort::class, new class implements AuditLogPort {
            /** @param array<string, mixed> $context */
            public function record(string $event, array $context = []): void
            {
                if ($event === 'note_revision_created') {
                    throw new RuntimeException('forced store-stock revision audit failure');
                }
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('forced store-stock revision audit failure');

        try {
            $this->app->make(CreateNoteRevisionHandler::class)->handle(
                'note-stock-rollback-001',
            array_replace($this->revisionPayload(), ['base_revision_id' => $this->revisionBaseForTest('note-stock-rollback-001')]),
                'admin-test-001',
                false,
            );
        } finally {
            $this->assertOriginalNoteAndRowsRemain();
            $this->assertInventorySideEffectsWereRolledBack();
            $this->assertRevisionArtifactsWereRolledBack();
            $this->assertProjectionWasRolledBack();
        }
    }

    private function seedOpenServiceStoreStockNote(): void
    {
        $this->seedNoteBase(
            'note-stock-rollback-001',
            'Budi Stock Rollback Original',
            '2026-05-20',
            350000,
            'open',
        );

        $this->seedNotePaymentProduct(
            'product-stock-rollback-001',
            'PRD-STOCK-RB-001',
            'Produk Stock Rollback',
            'Merek Rollback',
            100,
            100000,
        );

        $this->seedWorkItemBase(
            'wi-stock-rollback-old-001',
            'note-stock-rollback-001',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            350000,
        );

        $this->seedServiceDetailBase(
            'wi-stock-rollback-old-001',
            'Servis Stock Rollback Original',
            50000,
            'none',
        );

        $this->seedStoreStockLineBase(
            'ssl-stock-rollback-old-001',
            'wi-stock-rollback-old-001',
            'product-stock-rollback-001',
            3,
            300000,
        );

        $this->seedServiceWithStoreStockCurrentRevision(
            'note-stock-rollback-001',
            'note-stock-rollback-001-r001',
            'wi-stock-rollback-old-001',
            'Budi Stock Rollback Original',
            '2026-05-20',
            350000,
            'Servis Stock Rollback Original',
            50000,
            'ssl-stock-rollback-old-001',
            'product-stock-rollback-001',
            3,
            300000,
        );

        DB::table('product_inventory')->insert([
            'product_id' => 'product-stock-rollback-001',
            'qty_on_hand' => 7,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-stock-rollback-001',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 420000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'move-stock-rollback-old-001',
            'product_id' => 'product-stock-rollback-001',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-stock-rollback-old-001',
            'tanggal_mutasi' => '2026-05-20',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => -180000,
        ]);
    }

    /** @return array<string, mixed> */
    private function revisionPayload(): array
    {
        return [
            'reason' => 'Store-stock rollback characterization.',
            'note' => [
                'customer_name' => 'Budi Stock Rollback Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'store_stock',
                    'service' => [
                        'name' => 'Servis Stock Rollback Revised',
                        'price_rupiah' => 50000,
                        'notes' => null,
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'product-stock-rollback-001',
                            'qty' => 2,
                            'unit_price_rupiah' => 100000,
                            'price_basis' => 'revision_snapshot',
                        ],
                    ],
                    'external_purchase_lines' => [],
                ],
            ],
        ];
    }

    private function assertOriginalNoteAndRowsRemain(): void
    {
        $this->assertDatabaseHas('notes', [
            'id' => 'note-stock-rollback-001',
            'customer_name' => 'Budi Stock Rollback Original',
            'customer_phone' => null,
            'transaction_date' => '2026-05-20',
            'total_rupiah' => 350000,
            'current_revision_id' => 'note-stock-rollback-001-r001',
            'latest_revision_number' => 1,
        ]);

        $this->assertDatabaseMissing('notes', [
            'id' => 'note-stock-rollback-001',
            'customer_name' => 'Budi Stock Rollback Revised',
            'total_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-stock-rollback-old-001',
            'note_id' => 'note-stock-rollback-001',
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 350000,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-stock-rollback-old-001',
            'work_item_id' => 'wi-stock-rollback-old-001',
            'product_id' => 'product-stock-rollback-001',
            'qty' => 3,
            'line_total_rupiah' => 300000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-stock-rollback-old-001',
            'service_name' => 'Servis Stock Rollback Original',
            'service_price_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('work_item_service_details', [
            'service_name' => 'Servis Stock Rollback Revised',
            'service_price_rupiah' => 50000,
        ]);
    }

    private function assertInventorySideEffectsWereRolledBack(): void
    {
        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-stock-rollback-old-001',
            'product_id' => 'product-stock-rollback-001',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-stock-rollback-old-001',
            'tanggal_mutasi' => '2026-05-20',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => -180000,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-stock-rollback-001',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-stock-rollback-old-001',
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => 3,
        ]);

        self::assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_id', 'product-stock-rollback-001')
                ->where('movement_type', 'stock_out')
                ->where('source_type', 'work_item_store_stock_line')
                ->count(),
        );

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-stock-rollback-001',
            'qty_on_hand' => 7,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-stock-rollback-001',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 420000,
        ]);
    }

    private function assertRevisionArtifactsWereRolledBack(): void
    {
        $this->assertDatabaseMissing('note_revisions', [
            'id' => 'note-stock-rollback-001-r002',
            'note_root_id' => 'note-stock-rollback-001',
        ]);

        $this->assertDatabaseMissing('note_revision_settlements', [
            'id' => 'note-stock-rollback-001-r002-settlement',
            'note_root_id' => 'note-stock-rollback-001',
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
                ->where('note_id', 'note-stock-rollback-001')
                ->count(),
        );
    }
}
