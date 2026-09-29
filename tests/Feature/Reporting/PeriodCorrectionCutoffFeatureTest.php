<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetOperationalExpenseReportDatasetHandler;
use App\Application\Reporting\UseCases\GetPayrollReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PeriodCorrectionCutoffFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_later_payroll_reversal_preserves_prior_period_without_inventing_cash_inflow(): void
    {
        DB::table('employees')->insert(['id' => 'employee', 'employee_name' => 'Employee', 'default_salary_amount' => 1, 'salary_basis_type' => 'monthly', 'employment_status' => 'active']);
        DB::table('payroll_disbursements')->insert(['id' => 'salary', 'employee_id' => 'employee', 'amount' => 100001, 'disbursement_date' => '2026-07-31', 'mode' => 'monthly']);
        DB::table('payroll_disbursement_reversals')->insert(['id' => 'reverse', 'payroll_disbursement_id' => 'salary', 'reason' => 'Correction', 'performed_by_actor_id' => 'actor', 'created_at' => '2026-09-01 00:00:00']);
        $handler = app(GetPayrollReportDatasetHandler::class);
        self::assertSame(100001, $handler->handle('2026-07-01', '2026-07-31')->data()['summary']['total_amount_rupiah']);
        self::assertSame(0, $handler->handle('2026-09-01', '2026-09-30')->data()['summary']['total_amount_rupiah']);
    }

    public function test_later_expense_void_preserves_prior_period_without_inventing_cash_inflow(): void
    {
        DB::table('expense_categories')->insert(['id' => 'category', 'code' => 'TEST', 'name' => 'Test']);
        DB::table('operational_expenses')->insert(['id' => 'expense', 'category_id' => 'category', 'amount_rupiah' => 100001, 'expense_date' => '2026-07-31', 'description' => 'Test', 'payment_method' => 'cash', 'deleted_at' => '2026-09-30 23:59:59']);
        $handler = app(GetOperationalExpenseReportDatasetHandler::class);
        self::assertSame(100001, $handler->handle('2026-07-01', '2026-07-31')->data()['summary']['total_amount_rupiah']);
        self::assertSame(0, $handler->handle('2026-09-01', '2026-09-30')->data()['summary']['total_amount_rupiah']);
    }
}
