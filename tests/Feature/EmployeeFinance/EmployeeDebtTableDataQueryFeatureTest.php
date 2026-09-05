<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EmployeeDebtTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_employee_debt_table_with_multi_word_query(): void
    {
        $this->seedDebt('Asyraf Hutang', 1000000, 750000, 'unpaid', '2026-03-25 00:00:00');
        $this->seedDebt('Andi', 500000, 0, 'paid', '2026-03-20 00:00:00');

        $r = $this->actingAs($this->admin())->get(route('admin.employee-debts.table', ['q' => 'Asyraf Hutang']));
        $r->assertOk();
        $r->assertJsonCount(1, 'data.rows');
        $r->assertJsonPath('data.rows.0.employee_name', 'Asyraf Hutang');
        $r->assertJsonPath('data.meta.filters.q', 'Asyraf Hutang');
    }

    public function test_admin_can_sort_employee_debt_table_by_total_remaining_balance_desc(): void
    {
        $this->seedDebt('Budi', 1000000, 250000, 'unpaid', '2026-03-25 00:00:00');
        $this->seedDebt('Andi', 1000000, 800000, 'unpaid', '2026-03-24 00:00:00');

        $r = $this->actingAs($this->admin())->get(route('admin.employee-debts.table', ['sort_by' => 'total_remaining_balance', 'sort_dir' => 'desc']));
        $r->assertOk();
        $r->assertJsonPath('data.rows.0.employee_name', 'Andi');
        $r->assertJsonPath('data.rows.1.employee_name', 'Budi');
    }

    public function test_admin_can_filter_employee_debt_summary_by_active_status(): void
    {
        $this->seedDebt('Masih Aktif', 1000000, 250000, 'unpaid', '2026-03-25 00:00:00');
        $this->seedDebt('Sudah Lunas', 1000000, 0, 'paid', '2026-03-24 00:00:00');

        $response = $this->actingAs($this->admin())->getJson(route('admin.employee-debts.table', ['status' => 'active']));

        $response->assertOk()->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.employee_name', 'Masih Aktif')
            ->assertJsonPath('data.meta.filters.status', 'active');
    }

    public function test_admin_can_access_second_page_of_employee_debt_table(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->seedDebt('Employee '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 1000000, 500000, 'unpaid', '2026-03-25 00:00:00');
        }
        $r = $this->actingAs($this->admin())->get(route('admin.employee-debts.table', ['page' => 2, 'sort_by' => 'employee_name', 'sort_dir' => 'asc']));
        $r->assertOk();
        $r->assertJsonPath('data.meta.page', 2);
        $r->assertJsonPath('data.meta.last_page', 2);
        $r->assertJsonPath('data.rows.0.employee_name', 'Employee 11');
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);

        return $user;
    }

    private function seedDebt(string $name, int $total, int $remaining, string $status, string $createdAt): void
    {
        $employeeId = (string) Str::uuid();
        DB::table('employees')->insert(['id' => $employeeId, 'employee_name' => $name, 'phone' => '0812', 'default_salary_amount' => 5000000, 'salary_basis_type' => 'monthly', 'employment_status' => 'active']);
        DB::table('employee_debts')->insert(['id' => (string) Str::uuid(), 'employee_id' => $employeeId, 'total_debt' => $total, 'remaining_balance' => $remaining, 'status' => $status, 'created_at' => $createdAt]);
    }
}
