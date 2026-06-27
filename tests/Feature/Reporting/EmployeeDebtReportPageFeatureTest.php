<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeDebtReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_employee_debt_report_page(): void
    {
        $this->get(route('admin.reports.employee_debt.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_employee_debt_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.employee_debt.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_employee_debt_report_page_and_see_dataset_and_sidebar_routes(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');
        $this->seedEmployee('employee-3', 'Montir C');
        $this->seedEmployee('employee-4', 'Montir D');

        $this->seedDebt('debt-1', 'employee-1', 100000, 60000, 'unpaid', 'Kasbon A', '2030-01-07 08:00:00');
        $this->seedDebt('debt-2', 'employee-2', 50000, 50000, 'unpaid', 'Kasbon B', '2030-01-07 09:00:00');
        $this->seedDebt('debt-3', 'employee-3', 70000, 0, 'paid', 'Kasbon C', '2030-01-10 10:00:00');
        $this->seedDebt('debt-4', 'employee-4', 90000, 90000, 'unpaid', 'Kasbon D', '2030-01-10 11:00:00');

        $this->seedDebtPayment('payment-1', 'debt-1', 40000, '2030-01-08 08:00:00');
        $this->seedDebtPayment('payment-2', 'debt-3', 70000, '2030-01-11 08:00:00');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Laporan Hutang Karyawan');
        $response->assertSee('employee-debt-report-filter-form', false);
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rp 310.000');
        $response->assertSee('Rp 110.000');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Rincian Ringkas');
        $response->assertSee('Jumlah Data');
        $response->assertSee('Sisa Hutang');
        $response->assertDontSee('debt-1');
        $response->assertDontSee('debt-4');
        $response->assertSee('paid');
        $response->assertSee('unpaid');
        $response->assertDontSee('Detail Hutang');
        $response->assertSee(route('admin.reports.transaction_cash_ledger.index'), false);
        $response->assertSee(route('admin.reports.employee_debt.index'), false);
    }

    public function test_admin_sees_owner_readable_report_sections_on_employee_debt_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertDontSee('Catatan Laporan');
        $response->assertDontSee('Detail lengkap tersedia di Excel');
    }

    public function test_custom_mode_uses_explicit_date_range(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');
        $this->seedEmployee('employee-3', 'Montir C');

        $this->seedDebt('debt-in-1', 'employee-1', 100000, 60000, 'unpaid', 'Kasbon in 1', '2030-01-07 08:00:00');
        $this->seedDebt('debt-in-2', 'employee-2', 50000, 50000, 'unpaid', 'Kasbon in 2', '2030-01-09 09:00:00');
        $this->seedDebt('debt-out', 'employee-3', 90000, 90000, 'unpaid', 'Kasbon outside', '2030-01-11 10:00:00');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-07',
                'date_to' => '2030-01-09',
            ])
        );

        $response->assertOk();
        $response->assertSee('07 Januari 2030 s/d 09 Januari 2030');
        $response->assertSee('Rp 150.000');
        $response->assertDontSee('debt-in-1');
        $response->assertDontSee('debt-in-2');
        $response->assertDontSee('debt-out');
    }

    public function test_custom_mode_requires_explicit_date_range(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.index', [
                'period_mode' => 'custom',
            ])
        );

        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_custom_mode_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.employee_debt.index', [
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
            'email' => $role . '-employee-debt-report@example.test',
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
            'phone' => '081234567890',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedDebt(
        string $id,
        string $employeeId,
        int $totalDebt,
        int $remainingBalance,
        string $status,
        ?string $notes,
        string $createdAt,
    ): void {
        DB::table('employee_debts')->insert([
            'id' => $id,
            'employee_id' => $employeeId,
            'total_debt' => $totalDebt,
            'remaining_balance' => $remainingBalance,
            'status' => $status,
            'notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function seedDebtPayment(
        string $id,
        string $debtId,
        int $amount,
        string $paymentDate,
    ): void {
        DB::table('employee_debt_payments')->insert([
            'id' => $id,
            'employee_debt_id' => $debtId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'notes' => null,
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);
    }
}
