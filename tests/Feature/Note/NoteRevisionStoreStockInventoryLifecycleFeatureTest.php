<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteRevisionStoreStockInventoryLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_revision_reverses_old_store_stock_issue_and_issues_replacement_stock_once(): void
    {
        $this->seedOpenServiceStoreStockNote();

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-stock-revision-001',
            $this->revisionPayload(),
            'admin-test-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-stock-revision-001',
            'customer_name' => 'Budi Stock Revised',
            'transaction_date' => '2026-05-21',
            'total_rupiah' => 250000,
            'current_revision_id' => 'note-stock-revision-001-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-stock-revision-001-r002',
            'note_root_id' => 'note-stock-revision-001',
            'revision_number' => 2,
            'grand_total_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-stock-revision-001-r002-settlement',
            'note_revision_id' => 'note-stock-revision-001-r002',
            'note_root_id' => 'note-stock-revision-001',
            'gross_total_rupiah' => 250000,
            'carry_forward_paid_rupiah' => 0,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => 250000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);

        $this->assertDatabaseMissing('work_items', [
            'id' => 'wi-stock-revision-old-001',
            'note_id' => 'note-stock-revision-001',
        ]);

        $this->assertDatabaseMissing('work_item_store_stock_lines', [
            'id' => 'ssl-stock-revision-old-001',
            'work_item_id' => 'wi-stock-revision-old-001',
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-stock-revision-001',
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'subtotal_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'service_name' => 'Servis Stock Revised',
            'service_price_rupiah' => 50000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'product_id' => 'product-stock-revision-001',
            'qty' => 2,
            'line_total_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-stock-revision-old-001',
            'product_id' => 'product-stock-revision-001',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-stock-revision-old-001',
            'tanggal_mutasi' => '2026-05-20',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => -180000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-stock-revision-001',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-stock-revision-old-001',
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => 180000,
        ]);

        self::assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_id', 'product-stock-revision-001')
                ->where('movement_type', 'stock_in')
                ->where('source_type', 'transaction_workspace_updated')
                ->where('source_id', 'ssl-stock-revision-old-001')
                ->count(),
        );

        self::assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_id', 'product-stock-revision-001')
                ->where('movement_type', 'stock_out')
                ->where('source_type', 'work_item_store_stock_line')
                ->where('source_id', '<>', 'ssl-stock-revision-old-001')
                ->where('tanggal_mutasi', '2026-05-21')
                ->where('qty_delta', -2)
                ->where('unit_cost_rupiah', 60000)
                ->count(),
        );

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-stock-revision-001',
            'qty_on_hand' => 8,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-stock-revision-001',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 480000,
        ]);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => 'note-stock-revision-001',
            'customer_name' => 'Budi Stock Revised',
            'total_rupiah' => 250000,
            'allocated_rupiah' => 0,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => 250000,
        ]);
    }

    private function seedOpenServiceStoreStockNote(): void
    {
        $this->seedNoteBase(
            'note-stock-revision-001',
            'Budi Stock Original',
            '2026-05-20',
            350000,
            'open',
        );

        $this->seedNotePaymentProduct(
            'product-stock-revision-001',
            'PRD-STOCK-REV-001',
            'Produk Stock Revision',
            'Merek Revision',
            100,
            100000,
        );

        $this->seedWorkItemBase(
            'wi-stock-revision-old-001',
            'note-stock-revision-001',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            350000,
        );

        $this->seedServiceDetailBase(
            'wi-stock-revision-old-001',
            'Servis Stock Original',
            50000,
            'none',
        );

        $this->seedStoreStockLineBase(
            'ssl-stock-revision-old-001',
            'wi-stock-revision-old-001',
            'product-stock-revision-001',
            3,
            300000,
        );

        $this->seedServiceWithStoreStockCurrentRevision(
            'note-stock-revision-001',
            'note-stock-revision-001-r001',
            'wi-stock-revision-old-001',
            'Budi Stock Original',
            '2026-05-20',
            350000,
            'Servis Stock Original',
            50000,
            'ssl-stock-revision-old-001',
            'product-stock-revision-001',
            3,
            300000,
        );

        DB::table('product_inventory')->insert([
            'product_id' => 'product-stock-revision-001',
            'qty_on_hand' => 7,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-stock-revision-001',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 420000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'move-stock-revision-old-001',
            'product_id' => 'product-stock-revision-001',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-stock-revision-old-001',
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
            'reason' => 'Store-stock revision lifecycle characterization.',
            'note' => [
                'customer_name' => 'Budi Stock Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'store_stock',
                    'service' => [
                        'name' => 'Servis Stock Revised',
                        'price_rupiah' => 50000,
                        'notes' => null,
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'product-stock-revision-001',
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
}
