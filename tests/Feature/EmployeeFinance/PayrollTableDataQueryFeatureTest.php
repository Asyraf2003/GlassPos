<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PayrollTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_payroll_table_with_multi_word_query(): void
    {
        $this->seedPayrollRow('Budi Santoso', '2026-03-25 00:00:00', 5000000, 'monthly', 'Gaji Maret 2026');
        $this->seedPayrollRow('Andi', '2026-03-20 00:00:00', 3000000, 'weekly', 'Kasbon payroll');

        $r = $this->actingAs($this->admin())->get(route('admin.payrolls.table', ['q' => 'Budi Maret']));
        $r->assertOk();
        $r->assertJsonCount(1, 'data.rows');
        $r->assertJsonPath('data.rows.0.employee_name', 'Budi Santoso');
        $r->assertJsonPath('data.meta.filters.q', 'Budi Maret');
    }

    public function test_admin_can_sort_payroll_table_by_amount_desc(): void
    {
        $this->seedPayrollRow('Budi', '2026-03-25 00:00:00', 3000000, 'weekly', 'Gaji A');
        $this->seedPayrollRow('Andi', '2026-03-24 00:00:00', 6000000, 'monthly', 'Gaji B');

        $r = $this->actingAs($this->admin())->get(route('admin.payrolls.table', ['sort_by' => 'amount', 'sort_dir' => 'desc']));
        $r->assertOk();
        $r->assertJsonPath('data.rows.0.employee_name', 'Andi');
        $r->assertJsonPath('data.rows.1.employee_name', 'Budi');
    }

    public function test_admin_can_filter_payroll_by_mode_and_reversal_status(): void
    {
        $activeId = $this->seedPayrollRow('Aktif Bulanan', '2026-03-25 00:00:00', 3000000, 'monthly', 'Aktif');
        $reversedId = $this->seedPayrollRow('Batal Bulanan', '2026-03-24 00:00:00', 4000000, 'monthly', 'Batal');
        $this->seedPayrollRow('Aktif Mingguan', '2026-03-23 00:00:00', 1000000, 'weekly', 'Mingguan');
        DB::table('payroll_disbursement_reversals')->insert([
            'id' => 'payroll-reversal-filter', 'payroll_disbursement_id' => $reversedId,
            'reason' => 'Koreksi', 'performed_by_actor_id' => '1',
            'created_at' => '2026-03-25 12:00:00', 'updated_at' => '2026-03-25 12:00:00',
        ]);

        $response = $this->actingAs($this->admin())->getJson(route('admin.payrolls.table', [
            'mode' => 'monthly', 'status' => 'active',
        ]));

        $response->assertOk()->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.id', $activeId)
            ->assertJsonPath('data.meta.filters.mode', 'monthly')
            ->assertJsonPath('data.meta.filters.status', 'active');
    }

    public function test_default_search_prioritizes_exact_employee_name_over_notes_match(): void
    {
        $this->seedPayrollRow('Budi', '2026-03-20 00:00:00', 3000000, 'monthly', 'Gaji rutin');
        $this->seedPayrollRow('Zaki', '2026-03-25 00:00:00', 4000000, 'monthly', 'Koreksi Budi');

        $response = $this->actingAs($this->admin())->getJson(route('admin.payrolls.table', ['q' => 'Budi']));
        $response->assertOk()->assertJsonPath('data.rows.0.employee_name', 'Budi')
            ->assertJsonPath('data.meta.sort_by', 'relevance');
    }

    public function test_admin_can_access_second_page_of_payroll_table(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->seedPayrollRow('Employee '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), '2026-03-25 00:00:00', 1000000 + $i, 'monthly', 'Payroll '.$i);
        }

        $r = $this->actingAs($this->admin())->get(route('admin.payrolls.table', [
            'page' => 2,
            'sort_by' => 'employee_name',
            'sort_dir' => 'asc',
        ]));
        $r->assertOk();
        $r->assertJsonPath('data.meta.page', 2);
        $r->assertJsonPath('data.meta.last_page', 2);
        $r->assertJsonPath('data.rows.0.employee_name', 'Employee 11');
    }

    public function test_reversed_payroll_is_still_visible_in_global_payroll_table_with_reversal_flags(): void
    {
        $payrollId = $this->seedPayrollRow('Budi Reversal', '2026-03-25 00:00:00', 5000000, 'monthly', 'Gaji dibatalkan');

        DB::table('payroll_disbursement_reversals')->insert([
            'id' => (string) Str::uuid(),
            'payroll_disbursement_id' => $payrollId,
            'reason' => 'Koreksi payout payroll',
            'performed_by_actor_id' => '1',
            'created_at' => '2026-03-25 12:00:00',
            'updated_at' => '2026-03-25 12:00:00',
        ]);

        $r = $this->actingAs($this->admin())->get(route('admin.payrolls.table'));
        $r->assertOk();
        $r->assertJsonPath('data.rows.0.employee_name', 'Budi Reversal');
        $r->assertJsonPath('data.rows.0.is_reversed', true);
        $r->assertJsonPath('data.rows.0.reversal_reason', 'Koreksi payout payroll');
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);

        return $user;
    }

    private function seedPayrollRow(string $name, string $date, int $amount, string $mode, string $notes): string
    {
        $employeeId = (string) Str::uuid();
        $payrollId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => $name,
            'phone' => '0812',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
        ]);

        DB::table('payroll_disbursements')->insert([
            'id' => $payrollId,
            'employee_id' => $employeeId,
            'amount' => $amount,
            'disbursement_date' => $date,
            'mode' => $mode,
            'notes' => $notes,
        ]);

        return $payrollId;
    }
}
