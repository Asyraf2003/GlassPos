<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class InventoryStockValueReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_inventory_stock_value_report_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);
        $this->seedProduct('product-3', 'KB-003', 'Beat', 'Federal', 80, 16000);
        $this->seedProduct('product-4', 'KB-004', 'Scoopy', 'Federal', 85, 18000);
        $this->seedProduct('product-outside', 'KB-999', 'Outside', 'Federal', 88, 99000);

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
                'id' => 'm-outside',
                'product_id' => 'product-outside',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr-outside',
                'tanggal_mutasi' => '2030-02-01',
                'qty_delta' => 99,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 990000,
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

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-stok-persediaan-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'inventory-stock-value-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Snapshot Stok', 'Mutasi Periode'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $snapshot = $spreadsheet->getSheetByName('Snapshot Stok');
        $movement = $spreadsheet->getSheetByName('Mutasi Periode');

        $this->assertNotNull($summary);
        $this->assertNotNull($snapshot);
        $this->assertNotNull($movement);

        $this->assertSame('Stok dan Nilai Persediaan', $summary->getCell('A1')->getValue());
        $this->assertSame('01/01/2030 s/d 31/01/2030', $summary->getCell('B2')->getValue());
        $this->assertSame(4, $summary->getCell('B6')->getValue());
        $this->assertSame(2, $summary->getCell('B7')->getValue());
        $this->assertSame(21, $summary->getCell('B8')->getValue());
        $this->assertSame(211000, $summary->getCell('B9')->getValue());
        $this->assertSame(13, $summary->getCell('B10')->getValue());
        $this->assertSame(4, $summary->getCell('B11')->getValue());
        $this->assertSame(96000, $summary->getCell('B17')->getValue());

        $this->assertSame('Nama Barang', $snapshot->getCell('C1')->getValue());
        $this->assertSame('Supra', $snapshot->getCell('C2')->getValue());
        $this->assertSame(6, $snapshot->getCell('F2')->getValue());
        $this->assertSame(10000, $snapshot->getCell('G2')->getValue());
        $this->assertSame(60000, $snapshot->getCell('H2')->getValue());
        $this->assertSame('Scoopy', $snapshot->getCell('C5')->getValue());
        $this->assertNull($snapshot->getCell('C6')->getValue());

        $this->assertSame('Nama Barang', $movement->getCell('C1')->getValue());
        $this->assertSame('Supra', $movement->getCell('C2')->getValue());
        $this->assertSame(10, $movement->getCell('D2')->getValue());
        $this->assertSame(4, $movement->getCell('E2')->getValue());
        $this->assertSame(60000, $movement->getCell('M2')->getValue());
        $this->assertSame('Vario', $movement->getCell('C3')->getValue());
        $this->assertSame(36000, $movement->getCell('M3')->getValue());
        $this->assertNull($movement->getCell('C4')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_inventory_stock_value_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.inventory_stock_value.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_inventory_stock_value_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.export_excel', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2031-01-02',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export Excel maksimal 366 hari.');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-inventory-stock-value-report-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
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
