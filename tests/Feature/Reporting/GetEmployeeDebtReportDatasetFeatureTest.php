<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetEmployeeDebtReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetEmployeeDebtReportDatasetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_debt_report_dataset_returns_single_exact_dataset_from_summary_rows(): void
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

        $result = app(GetEmployeeDebtReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $rows = $data['rows'] ?? null;
        $summary = $data['summary'] ?? null;
        $periodRows = $data['period_rows'] ?? null;
        $statusRows = $data['status_rows'] ?? null;

        $this->assertIsArray($rows);
        $this->assertIsArray($summary);
        $this->assertIsArray($periodRows);
        $this->assertIsArray($statusRows);

        $this->assertCount(4, $rows);

        $this->assertSame([
            'opening_outstanding_rupiah' => 0,
            'new_debt_rupiah' => 310000,
            'adjustments_in_period_rupiah' => 0,
            'payments_in_period_rupiah' => 130000,
            'reversals_in_period_rupiah' => 20000,
            'total_rows' => 4,
            'total_debt' => 310000,
            'total_paid_amount' => 110000,
            'total_remaining_balance' => 200000,
            'paid_rows' => 1,
            'unpaid_rows' => 3,
        ], $summary);

        $this->assertSame([
            [
                'period_label' => '2030-01-07',
                'total_rows' => 2,
                'total_debt' => 150000,
                'total_paid_amount' => 40000,
                'total_remaining_balance' => 110000,
            ],
            [
                'period_label' => '2030-01-10',
                'total_rows' => 2,
                'total_debt' => 160000,
                'total_paid_amount' => 70000,
                'total_remaining_balance' => 90000,
            ],
        ], $periodRows);

        $this->assertSame([
            [
                'status' => 'paid',
                'total_rows' => 1,
                'total_debt' => 70000,
                'total_paid_amount' => 70000,
                'total_remaining_balance' => 0,
            ],
            [
                'status' => 'unpaid',
                'total_rows' => 3,
                'total_debt' => 240000,
                'total_paid_amount' => 40000,
                'total_remaining_balance' => 200000,
            ],
        ], $statusRows);

        $this->assertSame(
            $summary['total_debt'],
            array_sum(array_column($rows, 'total_debt'))
        );

        $this->assertSame(
            $summary['total_paid_amount'],
            array_sum(array_column($rows, 'total_paid_amount'))
        );

        $this->assertSame(
            $summary['total_remaining_balance'],
            array_sum(array_column($rows, 'remaining_balance'))
        );

        $this->assertSame(
            $summary['total_debt'],
            array_sum(array_column($periodRows, 'total_debt'))
        );

        $this->assertSame(
            $summary['total_paid_amount'],
            array_sum(array_column($periodRows, 'total_paid_amount'))
        );

        $this->assertSame(
            $summary['total_remaining_balance'],
            array_sum(array_column($periodRows, 'total_remaining_balance'))
        );

        $this->assertSame(
            $summary['total_debt'],
            array_sum(array_column($statusRows, 'total_debt'))
        );

        $this->assertSame(
            $summary['total_paid_amount'],
            array_sum(array_column($statusRows, 'total_paid_amount'))
        );

        $this->assertSame(
            $summary['total_remaining_balance'],
            array_sum(array_column($statusRows, 'total_remaining_balance'))
        );
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
