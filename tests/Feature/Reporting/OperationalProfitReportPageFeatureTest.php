<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OperationalProfitReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_operational_profit_report_page(): void
    {
        $this->get(route('admin.reports.operational_profit.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_operational_profit_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.operational_profit.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_operational_profit_report_page_and_see_cash_based_metrics(): void
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
            route('admin.reports.operational_profit.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-01',
            ])
        );

        $response->assertOk();
        $response->assertSee('Laba Kas Operasional');
        $response->assertSee('operational-profit-report-filter-form', false);
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Rp 10.000');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Rp 30.000');
        $response->assertSee('Rp 80.000');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Rp 40.000');
        $response->assertSee('Rp 15.000');
        $response->assertSee('Rp 35.000');
        $response->assertSee(route('admin.reports.transaction_cash_ledger.index'), false);
        $response->assertSee(route('admin.reports.employee_debt.index'), false);
        $response->assertSee(route('admin.reports.operational_profit.index'), false);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-operational-profit-report@example.test',
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
