<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CreatePayrollPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_create_payroll_page(): void
    {
        $this->get(route('admin.payrolls.create'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_create_payroll_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('kasir-payroll-create@example.test', 'kasir'))
            ->get(route('admin.payrolls.create'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_create_payroll_page(): void
    {
        DB::table('employees')->insert([
            'id' => (string) Str::uuid(),
            'employee_name' => 'Budi Payroll',
            'phone' => '081222222222',
            'default_salary_amount' => 4500000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->createUserWithRole('admin-payroll-create@example.test', 'admin'))
            ->get(route('admin.payrolls.create'));

        $response->assertOk();
        $response->assertSee('Form Pencairan Gaji');
        $response->assertSee('Cari Karyawan');
        $response->assertSee('Ketik minimal 2 huruf');
        $response->assertSee('Karyawan Terpilih');
        $response->assertSee('id="payroll-selected-card"', false);
        $response->assertSee('id="payroll-employee-remove"', false);
        $response->assertDontSee('value="Budi Payroll"', false);

        $employeeId = (string) DB::table('employees')->where('employee_name', 'Budi Payroll')->value('id');
        $oldPage = $this->withSession(['_old_input' => ['employee_id' => $employeeId, 'employee_lookup' => 'Budi Payroll']])
            ->get(route('admin.payrolls.create'));
        $oldPage->assertOk();
        $oldPage->assertSee('value="'.$employeeId.'"', false);
        $oldPage->assertDontSee('value="Budi Payroll"', false);
        $oldPage->assertSee('id="payroll-selected-card"', false);
        $response->assertSee('Nominal Pencairan');
        $response->assertSee('Tanggal Pencairan');
        $response->assertSee('Mode Pencairan');
        $response->assertSee('Simpan Pencairan Gaji');
        $response->assertSee('admin-payroll-create.js');
        $response->assertSee('autofocus', false);
    }

    public function test_admin_can_store_single_payroll_from_create_page(): void
    {
        $employeeId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Asyraf Mubarak',
            'phone' => '08111222333',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->createUserWithRole('admin-payroll-store@example.test', 'admin'))
            ->post(route('admin.payrolls.store'), [
                'employee_id' => $employeeId,
                'amount' => 5000000,
                'disbursement_date_string' => '2026-03-25',
                'mode_value' => 'monthly',
                'notes' => 'Gaji Maret 2026',
            ]);

        $response->assertRedirect(route('admin.payrolls.index'));
        $response->assertSessionHas('success', 'Pencairan gaji berhasil dicatat.');

        $this->assertDatabaseHas('payroll_disbursements', [
            'employee_id' => $employeeId,
            'amount' => 5000000,
            'mode' => 'monthly',
            'notes' => 'Gaji Maret 2026',
        ]);
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
