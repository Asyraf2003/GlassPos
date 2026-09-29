<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\EmployeeFinance\UseCases\PayEmployeeDebtHandler;
use App\Application\EmployeeFinance\UseCases\ReverseEmployeeDebtPaymentHandler;
use App\Application\Reporting\UseCases\GetEmployeeDebtSummaryHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeDebtSummaryHardeningFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_debt_summary_period_parity_matches_expected_totals_and_excludes_reversed_payments(): void
    {
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedEmployee('employee-2', 'Montir B');
        $this->seedEmployee('employee-3', 'Montir C');
        $this->seedEmployee('employee-4', 'Montir D');
        $this->seedEmployee('employee-5', 'Montir E');

        $this->seedDebt('debt-1', 'employee-1', 100000, 60000, 'unpaid', 'Kasbon A', '2030-01-07 08:00:00');
        $this->seedDebt('debt-2', 'employee-2', 50000, 50000, 'unpaid', 'Kasbon B', '2030-01-07 09:00:00');
        $this->seedDebt('debt-3', 'employee-3', 70000, 0, 'paid', 'Kasbon C', '2030-01-10 10:00:00');
        $this->seedDebt('debt-4', 'employee-4', 90000, 90000, 'unpaid', 'Kasbon D', '2030-01-10 11:00:00');
        $this->seedDebt('debt-5', 'employee-5', 110000, 110000, 'unpaid', 'Di luar scope', '2030-02-01 08:00:00');

        $this->seedDebtPayment('payment-1', 'debt-1', 40000, '2030-01-08 08:00:00');
        $this->seedDebtPayment('payment-2', 'debt-3', 70000, '2030-01-11 08:00:00');
        $this->seedDebtPayment('payment-3', 'debt-4', 20000, '2030-01-11 09:00:00');

        DB::table('employee_debt_payment_reversals')->insert([
            'id' => 'reversal-1',
            'employee_debt_payment_id' => 'payment-3',
            'reason' => 'Salah input',
            'performed_by_actor_id' => 'actor-admin-1',
            'created_at' => '2030-01-11 10:00:00',
            'updated_at' => '2030-01-11 10:00:00',
        ]);

        $daily = $this->reportTotals('2030-01-07', '2030-01-07');
        $weekly = $this->reportTotals('2030-01-07', '2030-01-13');
        $monthly = $this->reportTotals('2030-01-01', '2030-01-31');
        $custom = $this->reportTotals('2030-01-01', '2030-01-31');

        $this->assertSame([
            'total_rows' => 2,
            'total_debt' => 150000,
            'total_paid_amount' => 0,
            'total_remaining_balance' => 150000,
        ], $daily);

        $this->assertSame([
            'total_rows' => 4,
            'total_debt' => 310000,
            'total_paid_amount' => 110000,
            'total_remaining_balance' => 200000,
        ], $weekly);

        $this->assertSame([
            'total_rows' => 4,
            'total_debt' => 310000,
            'total_paid_amount' => 110000,
            'total_remaining_balance' => 200000,
        ], $monthly);

        $this->assertSame($monthly, $custom);
    }

    public function test_employee_debt_summary_changes_immediately_after_payment_and_reversal(): void
    {
        $this->seedEmployee('employee-10', 'Montir Step3');
        $this->seedDebt('debt-10', 'employee-10', 120000, 120000, 'unpaid', 'Kasbon step3', '2030-02-10 09:00:00');

        $before = app(GetEmployeeDebtSummaryHandler::class)->handle('2030-02-10', '2030-02-10');
        $this->assertTrue($before->isSuccess());
        $beforeRows = $before->data()['rows'] ?? [];
        $this->assertCount(1, $beforeRows);
        $this->assertSame(0, $beforeRows[0]['total_paid_amount']);
        $this->assertSame(120000, $beforeRows[0]['remaining_balance']);

        $paymentId = app(PayEmployeeDebtHandler::class)->handle('debt-10', 30000, 'Cicilan pertama');

        $afterPay = app(GetEmployeeDebtSummaryHandler::class)->handle('2030-02-10', '2030-02-10');
        $this->assertTrue($afterPay->isSuccess());
        $afterPayRows = $afterPay->data()['rows'] ?? [];
        $this->assertCount(1, $afterPayRows);
        $this->assertSame(30000, $afterPayRows[0]['total_paid_amount']);
        $this->assertSame(90000, $afterPayRows[0]['remaining_balance']);
        $this->assertSame('unpaid', $afterPayRows[0]['status']);

        app(ReverseEmployeeDebtPaymentHandler::class)->handle(
            $paymentId,
            'Salah input cicilan',
            'actor-admin-1',
        );

        $afterReverse = app(GetEmployeeDebtSummaryHandler::class)->handle('2030-02-10', '2030-02-10');
        $this->assertTrue($afterReverse->isSuccess());
        $afterReverseRows = $afterReverse->data()['rows'] ?? [];
        $this->assertCount(1, $afterReverseRows);
        $this->assertSame(0, $afterReverseRows[0]['total_paid_amount']);
        $this->assertSame(120000, $afterReverseRows[0]['remaining_balance']);
        $this->assertSame('unpaid', $afterReverseRows[0]['status']);
    }

    private function reportTotals(string $from, string $to): array
    {
        $result = app(GetEmployeeDebtSummaryHandler::class)->handle($from, $to);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $rows = $data['rows'] ?? [];
        $this->assertIsArray($rows);

        return [
            'total_rows' => count($rows),
            'total_debt' => array_sum(array_column($rows, 'total_debt')),
            'total_paid_amount' => array_sum(array_column($rows, 'total_paid_amount')),
            'total_remaining_balance' => array_sum(array_column($rows, 'remaining_balance')),
        ];
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
