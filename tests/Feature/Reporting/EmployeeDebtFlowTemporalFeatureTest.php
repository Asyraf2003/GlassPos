<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\OperationalProfit\OperatingCostMetricQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeDebtFlowTemporalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_additional_principal_is_flow_on_adjustment_date_not_original_debt_date(): void
    {
        DB::table('employees')->insert(['id' => 'e', 'employee_name' => 'A', 'default_salary_amount' => 1,
            'salary_basis_type' => 'monthly', 'employment_status' => 'active']);
        DB::table('employee_debts')->insert(['id' => 'd', 'employee_id' => 'e', 'total_debt' => 1000001,
            'remaining_balance' => 1000001, 'status' => 'unpaid', 'created_at' => '2026-07-01 00:00:00']);
        DB::table('employee_debt_adjustments')->insert(['id' => 'a', 'employee_debt_id' => 'd',
            'adjustment_type' => 'increase', 'amount' => 1, 'reason' => 'Additional loan',
            'performed_by_actor_id' => 'admin', 'before_total_debt' => 1000000, 'after_total_debt' => 1000001,
            'before_remaining_balance' => 1000000, 'after_remaining_balance' => 1000001,
            'created_at' => '2026-09-01 00:00:00']);
        $query = app(OperatingCostMetricQuery::class);
        self::assertSame(1000000, $query->employeeDebtCashOut('2026-07-01', '2026-07-31'));
        self::assertSame(1, $query->employeeDebtCashOut('2026-09-01', '2026-09-30'));
        self::assertSame(0, $query->employeeDebtCashOut('2026-08-01', '2026-08-31'));
    }
}
