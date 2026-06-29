<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryDeletedProductMovementReportVisibilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_stock_value_dataset_keeps_deleted_and_orphan_movements_with_safe_labels_without_snapshot_pollution(): void
    {
        $this->seedProduct('product-active', 'KB-ACT', 'Active Part');
        $this->seedProduct('product-deleted', 'KB-DEL', 'Deleted Part', '2030-01-15 10:00:00');

        DB::table('inventory_movements')->insert([
            [
                'id' => 'movement-active-in',
                'product_id' => 'product-active',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-active-line',
                'tanggal_mutasi' => '2030-01-10',
                'qty_delta' => 5,
                'unit_cost_rupiah' => 1000,
                'total_cost_rupiah' => 5000,
            ],
            [
                'id' => 'movement-deleted-in',
                'product_id' => 'product-deleted',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-deleted-line',
                'tanggal_mutasi' => '2030-01-11',
                'qty_delta' => 2,
                'unit_cost_rupiah' => 2000,
                'total_cost_rupiah' => 4000,
            ],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::table('inventory_movements')->insert([
                'id' => 'movement-orphan-in',
                'product_id' => 'product-orphan',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'receipt-orphan-line',
                'tanggal_mutasi' => '2030-01-12',
                'qty_delta' => 3,
                'unit_cost_rupiah' => 3000,
                'total_cost_rupiah' => 9000,
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-active', 'qty_on_hand' => 5],
            ['product_id' => 'product-deleted', 'qty_on_hand' => 2],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-active', 'avg_cost_rupiah' => 1000, 'inventory_value_rupiah' => 5000],
            ['product_id' => 'product-deleted', 'avg_cost_rupiah' => 2000, 'inventory_value_rupiah' => 4000],
        ]);

        $result = app(GetInventoryStockValueReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $snapshotRows = $data['snapshot_rows'] ?? [];
        $movementRows = $data['movement_rows'] ?? [];
        $summary = $data['summary'] ?? [];

        $this->assertIsArray($snapshotRows);
        $this->assertIsArray($movementRows);
        $this->assertIsArray($summary);

        $this->assertSame(['product-active'], array_column($snapshotRows, 'product_id'));

        $this->assertCount(3, $movementRows);

        $activeRow = $this->findMovementRow($movementRows, 'product-active');
        $deletedRow = $this->findMovementRow($movementRows, 'product-deleted');
        $orphanRow = $this->findMovementRow($movementRows, 'product-orphan');

        $this->assertSame('Active Part', $activeRow['nama_barang']);
        $this->assertSame('KB-ACT', $activeRow['kode_barang']);

        $this->assertSame('[Produk terhapus] Deleted Part', $deletedRow['nama_barang']);
        $this->assertSame('KB-DEL', $deletedRow['kode_barang']);

        $this->assertSame('[Produk tidak ditemukan: product-orphan]', $orphanRow['nama_barang']);
        $this->assertNull($orphanRow['kode_barang']);

        $this->assertSame(1, $summary['snapshot_product_rows']);
        $this->assertSame(3, $summary['movement_product_rows']);
        $this->assertSame(10, $summary['period_supply_in_qty']);
        $this->assertSame(10, $summary['period_net_qty_delta']);
        $this->assertSame(18000, $summary['period_net_cost_delta_rupiah']);
        $this->assertSame(5000, $summary['total_inventory_value_rupiah']);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function findMovementRow(array $rows, string $productId): array
    {
        foreach ($rows as $row) {
            if (($row['product_id'] ?? null) === $productId) {
                return $row;
            }
        }

        $this->fail(sprintf('Movement row not found for product_id [%s].', $productId));
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        ?string $deletedAt = null,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
            'deleted_at' => $deletedAt,
        ]);
    }
}
