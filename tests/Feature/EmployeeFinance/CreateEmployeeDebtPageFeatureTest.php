<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CreateEmployeeDebtPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_create_employee_debt_page(): void
    {
        $this->get(route('admin.employee-debts.create'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_create_employee_debt_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('kasir-debt-create@example.test', 'kasir'))
            ->get(route('admin.employee-debts.create'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_create_employee_debt_page(): void
    {
        DB::table('employees')->insert([
            'id' => (string) Str::uuid(),
            'employee_name' => 'Budi Hutang',
            'phone' => '081222222222',
            'default_salary_amount' => 4500000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->createUserWithRole('admin-debt-create@example.test', 'admin'))
            ->get(route('admin.employee-debts.create'));

        $response->assertOk();
        $response->assertSee('Catat Hutang Karyawan');
        $response->assertSee('Nominal Hutang');
        $response->assertSee('Simpan Data Hutang');
        $response->assertSee('id="employee-picker-selected"', false);
        $response->assertSee('id="employee-picker-remove"', false);
        $response->assertDontSee('value="Budi Hutang"', false);

        $employeeId = (string) DB::table('employees')->where('employee_name', 'Budi Hutang')->value('id');
        $oldPage = $this->withSession(['_old_input' => ['employee_id' => $employeeId, 'employee_lookup' => 'Budi Hutang']])
            ->get(route('admin.employee-debts.create'));
        $oldPage->assertOk();
        $oldPage->assertSee('value="'.$employeeId.'"', false);
        $oldPage->assertDontSee('value="Budi Hutang"', false);
        $oldPage->assertSee('id="employee-picker-selected"', false);
    }

    public function test_admin_can_store_employee_debt_from_create_page(): void
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

        $response = $this->actingAs($this->createUserWithRole('admin-debt-store@example.test', 'admin'))
            ->post(route('admin.employee-debts.store'), [
                'employee_id' => $employeeId,
                'debt_amount' => 1000000,
                'notes' => 'Pinjaman darurat',
            ]);

        $response->assertRedirect(route('admin.employee-debts.index'));
        $response->assertSessionHas('success', 'Data hutang karyawan berhasil dibuat.');

        $this->assertDatabaseHas('employee_debts', [
            'employee_id' => $employeeId,
            'total_debt' => 1000000,
            'remaining_balance' => 1000000,
            'status' => 'unpaid',
            'notes' => 'Pinjaman darurat',
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
