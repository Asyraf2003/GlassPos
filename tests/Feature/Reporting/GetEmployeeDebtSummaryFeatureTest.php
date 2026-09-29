<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetEmployeeDebtSummaryHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetEmployeeDebtSummaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_employee_debt_summary_handler_returns_debt_rows_and_passes_reconciliation(): void
    {
        $this->seedEmployee('employee-1', 'Montir A', '081111111111', 3000000, 'weekly', 'active');
        $this->seedEmployee('employee-2', 'Montir B', '081122223333', 3500000, 'monthly', 'active');
        $this->seedEmployee('employee-3', 'Montir C', '081133334444', 3200000, 'weekly', 'active');

        $this->seedEmployeeDebt(
            'debt-1',
            'employee-1',
            100000,
            40000,
            'unpaid',
            'Kasbon awal',
            '2026-03-15 08:00:00',
        );

        $this->seedEmployeeDebt(
            'debt-2',
            'employee-2',
            50000,
            0,
            'paid',
            'Kasbon lunas',
            '2026-03-16 09:30:00',
        );

        $this->seedEmployeeDebt(
            'debt-3',
            'employee-3',
            70000,
            70000,
            'unpaid',
            'Di luar scope',
            '2026-03-18 10:00:00',
        );

        $this->seedEmployeeDebtPayment('payment-1', 'debt-1', 30000, '2026-03-16 10:00:00');
        $this->seedEmployeeDebtPayment('payment-2', 'debt-1', 30000, '2026-03-20 10:00:00');
        $this->seedEmployeeDebtPayment('payment-3', 'debt-2', 50000, '2026-03-16 11:00:00');
        $this->seedEmployeeDebtPayment('payment-4', 'debt-3', 10000, '2026-03-18 12:00:00');

        $result = app(GetEmployeeDebtSummaryHandler::class)
            ->handle('2026-03-15', '2026-03-16');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('rows', $data);

        $this->assertSame([
            [
                'debt_id' => 'debt-1',
                'employee_id' => 'employee-1',
                'recorded_at' => '2026-03-15 08:00:00',
                'total_debt' => 100000,
                'total_paid_amount' => 30000,
                'remaining_balance' => 70000,
                'status' => 'unpaid',
                'notes' => 'Kasbon awal',
                'opening_outstanding_rupiah' => 0,
                'new_debt_rupiah' => 100000,
                'adjustments_in_period_rupiah' => 0,
                'payments_in_period_rupiah' => 30000,
                'reversals_in_period_rupiah' => 0,
            ],
            [
                'debt_id' => 'debt-2',
                'employee_id' => 'employee-2',
                'recorded_at' => '2026-03-16 09:30:00',
                'total_debt' => 50000,
                'total_paid_amount' => 50000,
                'remaining_balance' => 0,
                'status' => 'paid',
                'notes' => 'Kasbon lunas',
                'opening_outstanding_rupiah' => 0,
                'new_debt_rupiah' => 50000,
                'adjustments_in_period_rupiah' => 0,
                'payments_in_period_rupiah' => 50000,
                'reversals_in_period_rupiah' => 0,
            ],
        ], $data['rows']);
    }

    private function seedEmployee(
        string $id,
        string $name,
        ?string $phone,
        int $baseSalary,
        string $payPeriod,
        string $status,
    ): void {
        DB::table('employees')->insert([
            'id' => $id,
            'employee_name' => $name,
            'phone' => $phone,
            'salary_basis_type' => $payPeriod,
            'default_salary_amount' => $baseSalary,
            'employment_status' => $status,
            'started_at' => null,
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedEmployeeDebt(
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

    private function seedEmployeeDebtPayment(
        string $id,
        string $employeeDebtId,
        int $amount,
        string $paymentDate,
    ): void {
        DB::table('employee_debt_payments')->insert([
            'id' => $id,
            'employee_debt_id' => $employeeDebtId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'notes' => null,
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);
    }
}
