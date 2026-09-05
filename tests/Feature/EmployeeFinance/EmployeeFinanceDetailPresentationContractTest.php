<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use Tests\TestCase;

final class EmployeeFinanceDetailPresentationContractTest extends TestCase
{
    public function test_payroll_action_modal_keeps_detail_navigation_independent_from_optional_reversal_copy(): void
    {
        $script = (string) file_get_contents(public_path('assets/static/js/pages/admin-payrolls-table.js'));

        self::assertStringContainsString(
            'actionDetailPayrollLink.href = employeePayrollDetailUrl(row.employeeId);',
            $script,
        );
        self::assertStringNotContainsString('|| !actionReversalNote', $script);
    }

    public function test_employee_debt_detail_only_renders_history_sections_that_have_rows(): void
    {
        $view = (string) file_get_contents(resource_path('views/admin/employee_debts/show.blade.php'));

        self::assertStringContainsString("@if (! empty(\$detail['payments']))", $view);
        self::assertStringContainsString('@if ($paymentReversals !== [])', $view);
        self::assertStringContainsString('@if ($adjustments !== [])', $view);
        self::assertStringNotContainsString('Belum ada pembayaran hutang.', $view);
        self::assertStringNotContainsString('Belum ada reversal pembayaran hutang.', $view);
        self::assertStringNotContainsString('Belum ada koreksi hutang.', $view);
    }
}
