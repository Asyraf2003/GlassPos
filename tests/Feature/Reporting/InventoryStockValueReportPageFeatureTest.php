<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryStockValueReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_inventory_stock_value_report_page(): void
    {
        $this->get(route('admin.reports.inventory_stock_value.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_inventory_stock_value_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.inventory_stock_value.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_inventory_stock_value_report_page_and_see_sidebar_routes(): void
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
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Stok dan Nilai Persediaan');
        $response->assertSee('inventory-stock-value-report-filter-form', false);
        $response->assertSee('Notifikasi stok belum aktif.');
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rincian Ringkas');
        $response->assertSee('Qty Tersedia');
        $response->assertSee('Nilai Stok');
        $response->assertSee('Selisih Qty');
        $response->assertSee('Rp 211.000');
        $response->assertSee('Supra');
        $response->assertSee('Vario');
        $response->assertSee('Beat');
        $response->assertSee('Scoopy');
        $response->assertSee('Rp 96.000');
        $response->assertDontSee('Snapshot Stok Saat Ini');
        $response->assertDontSee('Ringkasan Mutasi Periode');
        $response->assertDontSee('KB-001');
        $response->assertSee(route('admin.reports.transaction_cash_ledger.index'), false);
        $response->assertSee(route('admin.reports.employee_debt.index'), false);
        $response->assertSee(route('admin.reports.operational_profit.index'), false);
        $response->assertSee(route('admin.reports.supplier_payable.index'), false);
        $response->assertSee(route('admin.reports.inventory_stock_value.index'), false);
    }

    public function test_admin_sees_owner_readable_report_sections_on_inventory_stock_value_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertSee('Catatan Laporan');
        $response->assertSee('Detail lengkap tersedia di Excel');
    }


    public function test_custom_mode_uses_explicit_date_range_for_period_movements(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);
        $this->seedProduct('product-out', 'KB-999', 'Outside', 'Federal', 80, 16000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'custom-m1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'custom-sr1',
                'tanggal_mutasi' => '2030-01-07',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'custom-m2',
                'product_id' => 'product-2',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'custom-sto1',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 12000,
                'total_cost_rupiah' => -48000,
            ],
            [
                'id' => 'custom-m-out',
                'product_id' => 'product-out',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'custom-sr-out',
                'tanggal_mutasi' => '2030-01-11',
                'qty_delta' => 99,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 990000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-1', 'qty_on_hand' => 10],
            ['product_id' => 'product-2', 'qty_on_hand' => 4],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-1', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 100000],
            ['product_id' => 'product-2', 'avg_cost_rupiah' => 12000, 'inventory_value_rupiah' => 48000],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-07',
                'date_to' => '2030-01-09',
            ])
        );

        $response->assertOk();
        $response->assertSee('07 Januari 2030 s/d 09 Januari 2030');
        $response->assertSee('Supra');
        $response->assertSee('Vario');
        $response->assertSee('Rp 52.000');
        $response->assertDontSee('Outside');
        $response->assertDontSee('custom-sr1');
        $response->assertDontSee('custom-sto1');
    }

    public function test_custom_mode_requires_explicit_date_range(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'custom',
            ])
        );

        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_custom_mode_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-10',
                'date_to' => '2030-01-01',
            ])
        );

        $response->assertSessionHasErrors(['date_from']);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-inventory-stock-value-report@example.test',
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
