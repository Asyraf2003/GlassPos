<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class OperationalProfitReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_operational_profit_report_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedEmployee('employee-1', 'Montir Profit');
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);
        $this->seedProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 100000);

        DB::table('notes')->insert([
            ['id' => 'note-1', 'customer_name' => 'Budi', 'transaction_date' => '2030-01-07', 'total_rupiah' => 200000],
            ['id' => 'note-2', 'customer_name' => 'Siti', 'transaction_date' => '2030-01-08', 'total_rupiah' => 100000],
        ]);

        DB::table('work_items')->insert([
            ['id' => 'wi-1', 'note_id' => 'note-1', 'line_no' => 1, 'transaction_type' => 'service_with_external_purchase', 'status' => 'open', 'subtotal_rupiah' => 200000],
            ['id' => 'wi-2', 'note_id' => 'note-2', 'line_no' => 1, 'transaction_type' => 'store_stock_sale_only', 'status' => 'open', 'subtotal_rupiah' => 100000],
        ]);

        DB::table('work_item_external_purchase_lines')->insert([
            ['id' => 'epl-1', 'work_item_id' => 'wi-1', 'cost_description' => 'Part luar', 'unit_cost_rupiah' => 50000, 'qty' => 1, 'line_total_rupiah' => 50000],
        ]);

        DB::table('customer_payments')->insert([
            ['id' => 'payment-1', 'amount_rupiah' => 200000, 'paid_at' => '2030-01-07'],
        ]);

        DB::table('customer_refunds')->insert([
            ['id' => 'refund-1', 'customer_payment_id' => 'payment-1', 'note_id' => 'note-1', 'amount_rupiah' => 10000, 'refunded_at' => '2030-01-08 10:00:00', 'reason' => 'Koreksi'],
        ]);

        DB::table('inventory_movements')->insert([
            ['id' => 'm1', 'product_id' => 'product-1', 'movement_type' => 'stock_out', 'source_type' => 'work_item_store_stock_line', 'source_id' => 'ssl-1', 'tanggal_mutasi' => '2030-01-08', 'qty_delta' => -2, 'unit_cost_rupiah' => 15000, 'total_cost_rupiah' => -30000],
        ]);

        DB::table('operational_expenses')->insert([
            ['id' => 'expense-1', 'category_id' => 'expense-category-1', 'amount_rupiah' => 20000, 'expense_date' => '2030-01-07', 'description' => 'Listrik', 'payment_method' => 'cash', 'reference_no' => null, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null],
        ]);

        DB::table('payroll_disbursements')->insert([
            ['id' => 'payroll-1', 'employee_id' => 'employee-1', 'amount' => 40000, 'disbursement_date' => '2030-01-08 12:00:00', 'mode' => 'weekly', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('employee_debts')->insert([
            ['id' => 'debt-1', 'employee_id' => 'employee-1', 'total_debt' => 15000, 'remaining_balance' => 15000, 'status' => 'unpaid', 'notes' => 'Kasbon report', 'created_at' => '2030-01-08 08:00:00', 'updated_at' => '2030-01-08 08:00:00'],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.operational_profit.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-01',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-laba-kas-operasional-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'operational-profit-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');

        $this->assertNotNull($summary);
        $this->assertSame('Laporan Laba Kas Operasional', $summary->getCell('A1')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame('Tanggal kejadian komponen kas dan biaya', $summary->getCell('B3')->getValue());
        $this->assertSame(200000, $summary->getCell('B6')->getValue());
        $this->assertSame(10000, $summary->getCell('B7')->getValue());
        $this->assertSame(50000, $summary->getCell('B8')->getValue());
        $this->assertSame(30000, $summary->getCell('B9')->getValue());
        $this->assertSame(80000, $summary->getCell('B10')->getValue());
        $this->assertSame(20000, $summary->getCell('B11')->getValue());
        $this->assertSame(40000, $summary->getCell('B12')->getValue());
        $this->assertSame(15000, $summary->getCell('B13')->getValue());
        $this->assertSame(35000, $summary->getCell('B14')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_operational_profit_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.operational_profit.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-operational-profit-report-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedEmployee(string $id, string $name): void
    {
        DB::table('employees')->insert([
            'id' => $id,
            'employee_name' => $name,
            'phone' => null,
            'salary_basis_type' => 'weekly',
            'default_salary_amount' => 3000000,
            'employment_status' => 'active',
            'started_at' => null,
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedExpenseCategory(string $id, string $code, string $name): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual
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
