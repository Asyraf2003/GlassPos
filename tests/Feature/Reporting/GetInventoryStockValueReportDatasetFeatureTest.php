<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetInventoryStockValueReportDatasetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_stock_value_report_dataset_returns_hybrid_snapshot_and_period_movement_exactly(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);
        $this->seedProduct('product-3', 'KB-003', 'Beat', 'Federal', 80, 16000);
        $this->seedProduct('product-4', 'KB-004', 'Scoopy', 'Federal', 85, 18000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr1',
                'tanggal_mutasi' => '2030-01-07',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'm2',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'sto1',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -40000,
            ],
            [
                'id' => 'm3',
                'product_id' => 'product-2',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr2',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => 3,
                'unit_cost_rupiah' => 12000,
                'total_cost_rupiah' => 36000,
            ],
            [
                'id' => 'm4',
                'product_id' => 'product-3',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr3',
                'tanggal_mutasi' => '2030-02-01',
                'qty_delta' => 5,
                'unit_cost_rupiah' => 9000,
                'total_cost_rupiah' => 45000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-1', 'qty_on_hand' => 6],
            ['product_id' => 'product-2', 'qty_on_hand' => 3],
            ['product_id' => 'product-3', 'qty_on_hand' => 5],
            ['product_id' => 'product-4', 'qty_on_hand' => 7],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-1', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 60000],
            ['product_id' => 'product-2', 'avg_cost_rupiah' => 12000, 'inventory_value_rupiah' => 36000],
            ['product_id' => 'product-3', 'avg_cost_rupiah' => 9000, 'inventory_value_rupiah' => 45000],
            ['product_id' => 'product-4', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 70000],
        ]);

        $result = app(GetInventoryStockValueReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $snapshotRows = $data['snapshot_rows'] ?? null;
        $movementRows = $data['movement_rows'] ?? null;
        $summary = $data['summary'] ?? null;

        $this->assertIsArray($snapshotRows);
        $this->assertIsArray($movementRows);
        $this->assertIsArray($summary);

        $this->assertSame([
            [
                'product_id' => 'product-1',
                'kode_barang' => 'KB-001',
                'nama_barang' => 'Supra',
                'merek' => 'Federal',
                'ukuran' => 100,
                'reorder_point_qty' => null,
                'critical_threshold_qty' => null,
                'current_qty_on_hand' => 6,
                'current_avg_cost_rupiah' => 10000,
                'current_inventory_value_rupiah' => 60000,
                'current_inventory_value_by_average_rupiah' => 60000,
                'current_rounding_residual_rupiah' => 0,
                'ledger_qty_on_hand' => 6,
                'ledger_inventory_value_rupiah' => 60000,
                'ledger_qty_diff' => 0,
                'ledger_value_diff_rupiah' => 0,
            ],
            [
                'product_id' => 'product-2',
                'kode_barang' => 'KB-002',
                'nama_barang' => 'Vario',
                'merek' => 'Federal',
                'ukuran' => 90,
                'reorder_point_qty' => null,
                'critical_threshold_qty' => null,
                'current_qty_on_hand' => 3,
                'current_avg_cost_rupiah' => 12000,
                'current_inventory_value_rupiah' => 36000,
                'current_inventory_value_by_average_rupiah' => 36000,
                'current_rounding_residual_rupiah' => 0,
                'ledger_qty_on_hand' => 3,
                'ledger_inventory_value_rupiah' => 36000,
                'ledger_qty_diff' => 0,
                'ledger_value_diff_rupiah' => 0,
            ],
        ], $snapshotRows);

        $this->assertSame([
            [
                'product_id' => 'product-1',
                'kode_barang' => 'KB-001',
                'nama_barang' => 'Supra',
                'supply_in_qty' => 10,
                'sale_out_qty' => 4,
                'refund_reversal_qty' => 0,
                'revision_correction_qty' => 0,
                'qty_in' => 10,
                'qty_out' => 4,
                'net_qty_delta' => 6,
                'total_in_cost_rupiah' => 100000,
                'total_out_cost_rupiah' => 40000,
                'net_cost_delta_rupiah' => 60000,
                'current_qty_on_hand' => 6,
                'current_avg_cost_rupiah' => 10000,
                'current_inventory_value_rupiah' => 60000,
            ],
            [
                'product_id' => 'product-2',
                'kode_barang' => 'KB-002',
                'nama_barang' => 'Vario',
                'supply_in_qty' => 3,
                'sale_out_qty' => 0,
                'refund_reversal_qty' => 0,
                'revision_correction_qty' => 0,
                'qty_in' => 3,
                'qty_out' => 0,
                'net_qty_delta' => 3,
                'total_in_cost_rupiah' => 36000,
                'total_out_cost_rupiah' => 0,
                'net_cost_delta_rupiah' => 36000,
                'current_qty_on_hand' => 3,
                'current_avg_cost_rupiah' => 12000,
                'current_inventory_value_rupiah' => 36000,
            ],
        ], $movementRows);

        $this->assertSame([
            'snapshot_product_rows' => 2,
            'movement_product_rows' => 2,
            'total_qty_on_hand' => 9,
            'total_inventory_value_rupiah' => 96000,
            'total_inventory_value_by_average_rupiah' => 96000,
            'total_rounding_residual_rupiah' => 0,
            'total_ledger_qty_diff' => 7,
            'total_ledger_value_diff_rupiah' => 70000,
            'stock_safe_product_rows' => 0,
            'stock_low_product_rows' => 0,
            'stock_critical_product_rows' => 0,
            'stock_unconfigured_product_rows' => 2,
            'period_supply_in_qty' => 13,
            'period_sale_out_qty' => 4,
            'period_refund_reversal_qty' => 0,
            'period_revision_correction_qty' => 0,
            'period_qty_in' => 13,
            'period_qty_out' => 4,
            'period_net_qty_delta' => 9,
            'period_total_in_cost_rupiah' => 136000,
            'period_total_out_cost_rupiah' => 40000,
            'period_net_cost_delta_rupiah' => 96000,
        ], $summary);

        $this->assertSame(
            $summary['total_qty_on_hand'],
            array_sum(array_column($snapshotRows, 'current_qty_on_hand'))
        );

        $this->assertSame(
            $summary['total_inventory_value_rupiah'],
            array_sum(array_column($snapshotRows, 'current_inventory_value_rupiah'))
        );

        $this->assertSame(
            $summary['period_net_cost_delta_rupiah'],
            array_sum(array_column($movementRows, 'net_cost_delta_rupiah'))
        );
    }

    public function test_inventory_stock_value_report_dataset_exposes_rounding_residual_separately_from_ledger_mismatch(): void
    {
        $this->seedProduct('product-residual', 'KB-RES', 'Residual Part', 'Federal', 30, 15000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'residual-m1',
                'product_id' => 'product-residual',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'residual-receipt-line-1',
                'tanggal_mutasi' => '2030-01-07',
                'qty_delta' => 30,
                'unit_cost_rupiah' => 1149,
                'total_cost_rupiah' => 34493,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-residual', 'qty_on_hand' => 30],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-residual', 'avg_cost_rupiah' => 1149, 'inventory_value_rupiah' => 34493],
        ]);

        $result = app(GetInventoryStockValueReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $snapshotRows = $data['snapshot_rows'] ?? [];
        $summary = $data['summary'] ?? [];

        $this->assertSame(1, count($snapshotRows));

        $row = $snapshotRows[0];

        $this->assertSame(30, $row['current_qty_on_hand']);
        $this->assertSame(1149, $row['current_avg_cost_rupiah']);
        $this->assertSame(34493, $row['current_inventory_value_rupiah']);

        $this->assertSame(34470, $row['current_inventory_value_by_average_rupiah']);
        $this->assertSame(23, $row['current_rounding_residual_rupiah']);

        $this->assertSame(30, $row['ledger_qty_on_hand']);
        $this->assertSame(34493, $row['ledger_inventory_value_rupiah']);
        $this->assertSame(0, $row['ledger_qty_diff']);
        $this->assertSame(0, $row['ledger_value_diff_rupiah']);

        $this->assertSame(34493, $summary['total_inventory_value_rupiah']);
        $this->assertSame(34470, $summary['total_inventory_value_by_average_rupiah']);
        $this->assertSame(23, $summary['total_rounding_residual_rupiah']);
        $this->assertSame(0, $summary['total_ledger_qty_diff']);
        $this->assertSame(0, $summary['total_ledger_value_diff_rupiah']);
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'merek' => $merek,
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
        ]);
    }
}
