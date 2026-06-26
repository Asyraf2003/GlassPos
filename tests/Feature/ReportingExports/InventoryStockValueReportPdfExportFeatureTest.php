<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryStockValueReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_inventory_stock_value_report_as_pdf(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);
        $this->seedProduct('product-outside', 'KB-999', 'Outside', 'Federal', 88, 99000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'pdf-m1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'pdf-sr1',
                'tanggal_mutasi' => '2030-01-07',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'pdf-m2',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'pdf-sto1',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -40000,
            ],
            [
                'id' => 'pdf-m3',
                'product_id' => 'product-2',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'pdf-sr2',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => 3,
                'unit_cost_rupiah' => 12000,
                'total_cost_rupiah' => 36000,
            ],
            [
                'id' => 'pdf-m-outside',
                'product_id' => 'product-outside',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'pdf-sr-outside',
                'tanggal_mutasi' => '2030-02-01',
                'qty_delta' => 99,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 990000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-1', 'qty_on_hand' => 6],
            ['product_id' => 'product-2', 'qty_on_hand' => 3],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-1', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 60000],
            ['product_id' => 'product-2', 'avg_cost_rupiah' => 12000, 'inventory_value_rupiah' => 36000],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-stok-persediaan-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_inventory_stock_value_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.inventory_stock_value.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_inventory_stock_value_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_inventory_stock_value_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.inventory_stock_value.export_pdf', [
            'title' => 'Stok dan Nilai Persediaan',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'referenceDateLabel' => '31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Produk Snapshot', 'value' => 2],
                ['label' => 'Nilai Persediaan', 'value' => 'Rp 96.000'],
                ['label' => 'Qty Masuk Pembelian', 'value' => 13],
                ['label' => 'Selisih Nilai Pokok Periode', 'value' => 'Rp 96.000'],
            ],
            'movementRows' => [
                [
                    'kode_barang' => 'KB-001',
                    'nama_barang' => 'Supra',
                    'supply_in_qty' => 10,
                    'sale_out_qty' => 4,
                    'refund_reversal_qty' => 0,
                    'revision_correction_qty' => 0,
                    'net_qty_delta' => 6,
                    'net_cost_delta' => 'Rp 60.000',
                    'current_qty_on_hand' => 6,
                    'current_inventory_value' => 'Rp 60.000',
                ],
            ],
            'snapshotRows' => [
                [
                    'kode_barang' => 'KB-001',
                    'nama_barang' => 'Supra',
                    'merek' => 'Federal',
                    'ukuran' => 100,
                    'current_qty_on_hand' => 6,
                    'current_avg_cost' => 'Rp 10.000',
                    'current_inventory_value' => 'Rp 60.000',
                    'reorder_point_qty' => '-',
                    'critical_threshold_qty' => '-',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Stok dan Nilai Persediaan', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringContainsString('Catatan Laporan', $html);
        $this->assertStringContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Mutasi Periode', $html);
        $this->assertStringNotContainsString('Snapshot Stok Saat Ini', $html);
        $this->assertStringNotContainsString('Supra', $html);
    }

    public function test_inventory_stock_value_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.inventory_stock_value.export_pdf', [
            'title' => 'Stok dan Nilai Persediaan',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'referenceDateLabel' => '31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Produk Snapshot', 'value' => 2],
                ['label' => 'Nilai Persediaan', 'value' => 'Rp 96.000'],
                ['label' => 'Qty Masuk Pembelian', 'value' => 13],
            ],
            'movementRows' => [
                [
                    'kode_barang' => 'KB-001',
                    'nama_barang' => 'Supra',
                    'supply_in_qty' => 10,
                    'sale_out_qty' => 4,
                    'refund_reversal_qty' => 0,
                    'revision_correction_qty' => 0,
                    'net_qty_delta' => 6,
                    'net_cost_delta' => 'Rp 60.000',
                    'current_qty_on_hand' => 6,
                    'current_inventory_value' => 'Rp 60.000',
                ],
            ],
            'snapshotRows' => [
                [
                    'kode_barang' => 'KB-001',
                    'nama_barang' => 'Supra',
                    'merek' => 'Federal',
                    'ukuran' => 100,
                    'current_qty_on_hand' => 6,
                    'current_avg_cost' => 'Rp 10.000',
                    'current_inventory_value' => 'Rp 60.000',
                    'reorder_point_qty' => '-',
                    'critical_threshold_qty' => '-',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringContainsString('Catatan Laporan', $html);
        $this->assertStringContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('summary-grid', $html);
        $this->assertStringNotContainsString('<table>', $html);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-inventory-stock-value-report-pdf-export@example.test',
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
