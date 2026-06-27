<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PayrollReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_payroll_report_page(): void
    {
        $this->get(route('admin.reports.payroll.index'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_payroll_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.reports.payroll.index'));
        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_payroll_report_page_and_see_report_data(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');
        $this->seedPayroll('payroll-1', 'employee-1', 50000, '2030-01-06 08:00:00', 'daily', 'Harian A');
        $this->seedPayroll('payroll-2', 'employee-2', 40000, '2030-01-07 09:00:00', 'weekly', 'Mingguan B');
        $this->seedPayroll('payroll-3', 'employee-1', 10000, '2030-01-07 10:00:00', 'daily', 'Harian A2');

        $response = $this->actingAs($this->user('admin'))->get(route('admin.reports.payroll.index', [
            'period_mode' => 'monthly',
            'reference_date' => '2030-01-01',
        ]));

        $response->assertOk();
        $response->assertSee('Laporan Gaji');
        $response->assertSee('payroll-report-filter-form', false);
        $response->assertSee('value="custom"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rincian Ringkas');
        $response->assertSee('Jumlah Pencairan');
        $response->assertSee('Total Nominal');
        $response->assertSee('Mode Terbesar');
        $response->assertDontSee('Montir A');
        $response->assertDontSee('Montir B');
        $response->assertSee('Harian');
        $response->assertSee('Rp 100.000');
        $response->assertSee('07 Januari 2030');
        $response->assertDontSee('Detail Pencairan Gaji');
    }

    public function test_admin_sees_owner_readable_report_sections_on_payroll_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(route('admin.reports.payroll.index', [
            'period_mode' => 'monthly',
            'reference_date' => '2030-01-01',
        ]));

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertDontSee('Catatan Laporan');
        $response->assertDontSee('Detail lengkap tersedia di Excel');
    }

    public function test_custom_mode_uses_explicit_date_range(): void
    {
        $this->seedEmployee('employee-custom-1', 'Montir Custom A');
        $this->seedEmployee('employee-custom-2', 'Montir Custom B');
        $this->seedPayroll('payroll-custom-1', 'employee-custom-1', 50000, '2030-01-06 08:00:00', 'daily', 'Outside A');
        $this->seedPayroll('payroll-custom-2', 'employee-custom-2', 40000, '2030-01-07 09:00:00', 'weekly', 'Inside B');
        $this->seedPayroll('payroll-custom-3', 'employee-custom-1', 10000, '2030-01-08 10:00:00', 'daily', 'Outside C');

        $response = $this->actingAs($this->user('admin'))->get(route('admin.reports.payroll.index', [
            'period_mode' => 'custom',
            'date_from' => '2030-01-07',
            'date_to' => '2030-01-07',
        ]));

        $response->assertOk();
        $response->assertSee('07 Januari 2030 s/d 07 Januari 2030');
        $response->assertSee('Mingguan');
        $response->assertDontSee('Montir Custom B');
        $response->assertDontSee('Montir Custom A');
        $response->assertSee('Rp 40.000');
        $response->assertDontSee('Rp 50.000');
    }

    public function test_custom_mode_requires_explicit_date_range(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.reports.payroll.index'))
            ->get(route('admin.reports.payroll.index', [
                'period_mode' => 'custom',
            ]));

        $response->assertRedirect(route('admin.reports.payroll.index'));
        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_custom_mode_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.reports.payroll.index'))
            ->get(route('admin.reports.payroll.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-08',
                'date_to' => '2030-01-07',
            ]));

        $response->assertRedirect(route('admin.reports.payroll.index'));
        $response->assertSessionHasErrors(['date_from']);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-payroll-report@example.test',
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
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPayroll(string $id, string $employeeId, int $amount, string $date, string $mode, ?string $notes): void
    {
        DB::table('payroll_disbursements')->insert([
            'id' => $id,
            'employee_id' => $employeeId,
            'amount' => $amount,
            'disbursement_date' => $date,
            'mode' => $mode,
            'notes' => $notes,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
