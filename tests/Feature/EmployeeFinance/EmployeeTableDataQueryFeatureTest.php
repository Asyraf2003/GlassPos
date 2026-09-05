<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_employee_table_with_multi_word_query(): void
    {
        $this->seedEmployeeRow('emp-1', 'Budi Santoso', '08121111', 5000000, 'weekly', 'active');
        $this->seedEmployeeRow('emp-2', 'Andi', '08999999', 4000000, 'monthly', 'inactive');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.employees.table', ['q' => 'Budi 0812']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.employee_name', 'Budi Santoso');
        $response->assertJsonPath('data.meta.filters.q', 'Budi 0812');
    }

    public function test_admin_can_sort_employee_table_by_default_salary_amount_desc(): void
    {
        $this->seedEmployeeRow('emp-1', 'Budi', '0812', 3000000, 'weekly', 'active');
        $this->seedEmployeeRow('emp-2', 'Andi', '0822', 6000000, 'monthly', 'active');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.employees.table', [
                'sort_by' => 'default_salary_amount',
                'sort_dir' => 'desc',
            ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.employee_name', 'Andi');
        $response->assertJsonPath('data.rows.1.employee_name', 'Budi');
    }

    public function test_admin_can_filter_employee_status_and_salary_basis(): void
    {
        $this->seedEmployeeRow('emp-active-weekly', 'Budi Mingguan', '0811', 3000000, 'weekly', 'active');
        $this->seedEmployeeRow('emp-inactive-weekly', 'Andi Mingguan', '0822', 3000000, 'weekly', 'inactive');
        $this->seedEmployeeRow('emp-active-monthly', 'Cici Bulanan', '0833', 3000000, 'monthly', 'active');

        $response = $this->actingAs($this->admin())->getJson(route('admin.employees.table', [
            'employment_status' => 'active',
            'salary_basis_type' => 'weekly',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.id', 'emp-active-weekly')
            ->assertJsonPath('data.meta.filters.employment_status', 'active')
            ->assertJsonPath('data.meta.filters.salary_basis_type', 'weekly');
    }

    public function test_default_search_prioritizes_exact_employee_name_over_phone_contains_match(): void
    {
        $this->seedEmployeeRow('emp-exact', 'Budi', '0800', 3000000, 'weekly', 'active');
        $this->seedEmployeeRow('emp-phone', 'Zaki', '0812-Budi', 3000000, 'weekly', 'active');

        $response = $this->actingAs($this->admin())->getJson(route('admin.employees.table', ['q' => 'Budi']));

        $response->assertOk()
            ->assertJsonPath('data.rows.0.id', 'emp-exact')
            ->assertJsonPath('data.meta.sort_by', 'relevance');
    }

    public function test_admin_can_access_second_page_of_employee_table(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->seedEmployeeRow(
                'emp-'.$i,
                'Employee '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                '08'.$i,
                1000000 + $i,
                'monthly',
                'active'
            );
        }

        $response = $this->actingAs($this->admin())
            ->get(route('admin.employees.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.rows.0.employee_name', 'Employee 11');
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function seedEmployeeRow(
        string $id,
        string $name,
        string $phone,
        int $salary,
        string $period,
        string $status
    ): void {
        DB::table('employees')->insert([
            'id' => $id,
            'employee_name' => $name,
            'phone' => $phone,
            'default_salary_amount' => $salary,
            'salary_basis_type' => $period,
            'employment_status' => $status,
        ]);
    }
}
