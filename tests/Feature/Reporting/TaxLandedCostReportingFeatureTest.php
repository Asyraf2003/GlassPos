<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TaxLandedCostReportingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_stock_value_report_uses_taxed_landed_cost_projection(): void
    {
        $this->seedProduct('product-tax-report-1', 'TAX-RPT-001', 'Ban Pajak Report', 'Federal', 90, 35000);

        DB::table('inventory_movements')->insert([
            'id' => 'tax-report-stock-in-1',
            'product_id' => 'product-tax-report-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'supplier-receipt-line-tax-report-1',
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 11000,
            'total_cost_rupiah' => 22000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-tax-report-1',
            'qty_on_hand' => 2,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-tax-report-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'custom',
                'date_from' => '2026-03-15',
                'date_to' => '2026-03-15',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ban Pajak Report');
        $response->assertSee('Rp 22.000');
        $response->assertSee('Rincian Ringkas');
        $response->assertDontSee('Rp 11.000');
        $response->assertDontSee('TAX-RPT-001');
    }

    public function test_inventory_stock_value_report_period_movements_include_taxed_supplier_receipt_cost(): void
    {
        $this->seedProduct('product-tax-report-1', 'TAX-RPT-001', 'Ban Pajak Report', 'Federal', 90, 35000);
        $this->seedProduct('product-tax-report-outside', 'TAX-RPT-999', 'Luar Periode', 'Federal', 90, 35000);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'tax-report-stock-in-1',
                'product_id' => 'product-tax-report-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'supplier-receipt-line-tax-report-1',
                'tanggal_mutasi' => '2026-03-15',
                'qty_delta' => 2,
                'unit_cost_rupiah' => 11000,
                'total_cost_rupiah' => 22000,
            ],
            [
                'id' => 'tax-report-stock-in-outside',
                'product_id' => 'product-tax-report-outside',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'supplier-receipt-line-tax-report-outside',
                'tanggal_mutasi' => '2026-04-01',
                'qty_delta' => 9,
                'unit_cost_rupiah' => 99999,
                'total_cost_rupiah' => 899991,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-tax-report-1', 'qty_on_hand' => 2],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-tax-report-1', 'avg_cost_rupiah' => 11000, 'inventory_value_rupiah' => 22000],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'custom',
                'date_from' => '2026-03-15',
                'date_to' => '2026-03-15',
            ])
        );

        $response->assertOk();
        $response->assertSee('15 Maret 2026 s/d 15 Maret 2026');
        $response->assertSee('Ban Pajak Report');
        $response->assertSee('Rp 22.000');
        $response->assertDontSee('Luar Periode');
    }

    public function test_operational_profit_summary_uses_taxed_landed_cost_as_store_stock_cogs(): void
    {
        $this->seedProduct('product-tax-report-1', 'TAX-RPT-001', 'Ban Pajak Report', 'Federal', 90, 35000);

        DB::table('customer_payments')->insert([
            'id' => 'tax-report-payment-1',
            'amount_rupiah' => 50000,
            'paid_at' => '2026-03-16 10:00:00',
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'tax-report-stock-out-1',
            'product_id' => 'product-tax-report-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'store-stock-line-tax-report-1',
            'tanggal_mutasi' => '2026-03-16',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 11000,
            'total_cost_rupiah' => -11000,
        ]);

        $row = $this->operationalProfitRow('2026-03-16', '2026-03-16');

        $this->assertSame(50000, $row['cash_in_rupiah']);
        $this->assertSame(11000, $row['store_stock_cogs_rupiah']);
        $this->assertSame(11000, $row['product_purchase_cost_rupiah']);
        $this->assertSame(39000, $row['cash_operational_profit_rupiah']);
    }

    public function test_operational_profit_summary_sums_multiple_taxed_store_stock_cogs_lines(): void
    {
        $this->seedProduct('product-tax-report-1', 'TAX-RPT-001', 'Ban Pajak Report', 'Federal', 90, 35000);
        $this->seedProduct('product-tax-report-2', 'TAX-RPT-002', 'Oli Pajak Report', 'Federal', 1, 55000);

        DB::table('customer_payments')->insert([
            'id' => 'tax-report-payment-1',
            'amount_rupiah' => 80000,
            'paid_at' => '2026-03-16 10:00:00',
        ]);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'tax-report-stock-out-1',
                'product_id' => 'product-tax-report-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'store-stock-line-tax-report-1',
                'tanggal_mutasi' => '2026-03-16',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 11000,
                'total_cost_rupiah' => -11000,
            ],
            [
                'id' => 'tax-report-stock-out-2',
                'product_id' => 'product-tax-report-2',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'store-stock-line-tax-report-2',
                'tanggal_mutasi' => '2026-03-16',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 22000,
                'total_cost_rupiah' => -22000,
            ],
        ]);

        $row = $this->operationalProfitRow('2026-03-16', '2026-03-16');

        $this->assertSame(33000, $row['store_stock_cogs_rupiah']);
        $this->assertSame(33000, $row['product_purchase_cost_rupiah']);
        $this->assertSame(47000, $row['cash_operational_profit_rupiah']);
    }

    public function test_operational_profit_summary_nets_taxed_store_stock_reversal_against_cogs(): void
    {
        $this->seedProduct('product-tax-report-1', 'TAX-RPT-001', 'Ban Pajak Report', 'Federal', 90, 35000);

        DB::table('customer_payments')->insert([
            'id' => 'tax-report-payment-1',
            'amount_rupiah' => 50000,
            'paid_at' => '2026-03-16 10:00:00',
        ]);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'tax-report-stock-out-1',
                'product_id' => 'product-tax-report-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'store-stock-line-tax-report-1',
                'tanggal_mutasi' => '2026-03-16',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 11000,
                'total_cost_rupiah' => -11000,
            ],
            [
                'id' => 'tax-report-stock-in-reversal-1',
                'product_id' => 'product-tax-report-1',
                'movement_type' => 'stock_in',
                'source_type' => 'work_item_store_stock_line_reversal',
                'source_id' => 'store-stock-line-tax-report-1',
                'tanggal_mutasi' => '2026-03-16',
                'qty_delta' => 1,
                'unit_cost_rupiah' => 11000,
                'total_cost_rupiah' => 11000,
            ],
        ]);

        $row = $this->operationalProfitRow('2026-03-16', '2026-03-16');

        $this->assertSame(0, $row['store_stock_cogs_rupiah']);
        $this->assertSame(0, $row['product_purchase_cost_rupiah']);
        $this->assertSame(50000, $row['cash_operational_profit_rupiah']);
    }

    /**
     * @return array<string, int|string>
     */
    private function operationalProfitRow(string $fromDate, string $toDate): array
    {
        $result = app(GetOperationalProfitSummaryHandler::class)->handle($fromDate, $toDate);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('row', $data);
        $this->assertIsArray($data['row']);

        return $data['row'];
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-tax-landed-cost-reporting@example.test',
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
            'nama_barang_normalized' => mb_strtolower(trim($namaBarang)),
            'merek' => $merek,
            'merek_normalized' => mb_strtolower(trim($merek)),
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }
}
