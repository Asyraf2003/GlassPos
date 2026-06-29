<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\Inventory\UseCases\RebuildInventoryCostingProjectionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalInventoryProductFixture;
use Tests\TestCase;

final class RebuildInventoryCostingProjectionWithStockOutFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalInventoryProductFixture;

    public function test_rebuild_costing_projection_handles_value_only_cost_revaluation(): void
    {
        $this->seedInventoryProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 12000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-line-1',
                'tanggal_mutasi' => '2026-03-16',
                'qty_delta' => 2,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 20000,
            ],
            [
                'id' => 'm2',
                'product_id' => 'product-1',
                'movement_type' => 'cost_revaluation',
                'source_type' => 'supplier_invoice_cost_revaluation',
                'source_id' => 'invoice-line-2',
                'tanggal_mutasi' => '2026-03-17',
                'qty_delta' => 0,
                'unit_cost_rupiah' => 0,
                'total_cost_rupiah' => 2000,
            ],
        ]);

        $handler = app(RebuildInventoryCostingProjectionHandler::class);

        $handler->handle();

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);
    }

    public function test_rebuild_costing_projection_handles_stock_out(): void
    {
        $this->seedInventoryProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 12000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'receipt',
                'source_id' => 'r1',
                'tanggal_mutasi' => '2026-03-01',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'm2',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'note',
                'source_id' => 'n1',
                'tanggal_mutasi' => '2026-03-02',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -40000,
            ],
        ]);

        $handler = app(RebuildInventoryCostingProjectionHandler::class);

        $handler->handle();

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 60000,
        ]);
    }
    public function test_rebuild_costing_projection_does_not_skip_same_day_stock_out_before_stock_in(): void
    {
        $this->seedInventoryProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 12000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1-stock-out-first',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'work-line-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 1150,
                'total_cost_rupiah' => -1150,
            ],
            [
                'id' => 'm2-reversal',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'work_item_store_stock_line_reversal',
                'source_id' => 'work-line-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => 1,
                'unit_cost_rupiah' => 1150,
                'total_cost_rupiah' => 1150,
            ],
            [
                'id' => 'm3-receipt',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-line-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 1150,
                'total_cost_rupiah' => 11500,
            ],
            [
                'id' => 'm4-adjustment-out',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'stock_adjustment',
                'source_id' => 'adjustment-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 1150,
                'total_cost_rupiah' => -1150,
            ],
            [
                'id' => 'm5-cost-revaluation',
                'product_id' => 'product-1',
                'movement_type' => 'cost_revaluation',
                'source_type' => 'supplier_invoice_cost_revaluation',
                'source_id' => 'invoice-line-1',
                'tanggal_mutasi' => '2026-06-30',
                'qty_delta' => 0,
                'unit_cost_rupiah' => 0,
                'total_cost_rupiah' => 300,
            ],
        ]);

        $handler = app(RebuildInventoryCostingProjectionHandler::class);

        $handler->handle();

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 1183,
            'inventory_value_rupiah' => 10650,
        ]);
    }

    public function test_rebuild_costing_projection_removes_stale_projection_when_product_ledger_net_qty_is_zero(): void
    {
        $this->seedInventoryProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 12000);
        $this->seedInventoryProduct('product-2', 'KB-002', 'Ban Dalam', 'Federal', 90, 15000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1-product-1-stock-in',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-line-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => 5,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 50000,
            ],
            [
                'id' => 'm2-product-1-stock-out',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'work-line-1',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => -5,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -50000,
            ],
            [
                'id' => 'm3-product-2-stock-in',
                'product_id' => 'product-2',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-line-2',
                'tanggal_mutasi' => '2026-06-29',
                'qty_delta' => 3,
                'unit_cost_rupiah' => 15000,
                'total_cost_rupiah' => 45000,
            ],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-1',
                'avg_cost_rupiah' => 99999,
                'inventory_value_rupiah' => 99999,
            ],
            [
                'product_id' => 'product-2',
                'avg_cost_rupiah' => 99999,
                'inventory_value_rupiah' => 99999,
            ],
        ]);

        $handler = app(RebuildInventoryCostingProjectionHandler::class);

        $handler->handle();

        $this->assertDatabaseMissing('product_inventory_costing', [
            'product_id' => 'product-1',
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-2',
            'avg_cost_rupiah' => 15000,
            'inventory_value_rupiah' => 45000,
        ]);

        $this->assertDatabaseCount('product_inventory_costing', 1);
    }


}
