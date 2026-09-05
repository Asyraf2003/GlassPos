<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\EmployeeFinance\UseCases\PayrollBatchRowProcessor;
use App\Application\EmployeeFinance\UseCases\RecordEmployeeDebtHandler;
use App\Application\EmployeeFinance\UseCases\RegisterEmployeeHandler;
use App\Core\EmployeeFinance\Payroll\DisbursementMode;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Database\Seeders\DefaultSeed\Support\DefaultSeedWindow;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultEmployeeFinanceSeeder extends Seeder
{
    public function run(
        RegisterEmployeeHandler $employees,
        PayrollBatchRowProcessor $payroll,
        RecordEmployeeDebtHandler $debts,
    ): void {
        $actorId = DefaultSeedActor::adminId();

        for ($index = 0; $index < 10; $index++) {
            $name = sprintf('Default Employee %02d', $index + 1);
            $employeeId = DB::table('employees')->where('employee_name', $name)->value('id');
            $salary = 2500000 + ($index * 150000);

            if ($employeeId === null) {
                $employeeId = $employees->handle(
                    $name,
                    sprintf('0812%08d', 10000000 + $index),
                    $salary,
                    'monthly',
                    DefaultSeedWindow::start()->addDays($index)->format('Y-m-d'),
                );
            }

            $employeeId = (string) $employeeId;
            $this->seedPayroll($payroll, $actorId, $employeeId, $salary, $index);

            $debtNote = sprintf('Default seed debt %02d', $index + 1);
            if (! DB::table('employee_debts')->where('employee_id', $employeeId)->where('notes', $debtNote)->exists()) {
                $debts->handle($employeeId, 300000 + ($index * 75000), $debtNote, $actorId);
            }
        }
    }

    private function seedPayroll(
        PayrollBatchRowProcessor $payroll,
        string $actorId,
        string $employeeId,
        int $salary,
        int $employeeIndex,
    ): void {
        for ($month = 1; $month <= 6; $month++) {
            $date = DefaultSeedWindow::start()->addMonthsNoOverflow($month);
            $note = sprintf('Default seed payroll E%02d-M%02d', $employeeIndex + 1, $month);

            if (DB::table('payroll_disbursements')->where('employee_id', $employeeId)->where('notes', $note)->exists()) {
                continue;
            }

            $result = $payroll->process(
                'default-seed-'.$date->format('Ym'),
                $actorId,
                new DateTimeImmutable($date->format('Y-m-d')),
                DisbursementMode::MONTHLY,
                $note,
                ['employee_id' => $employeeId, 'amount' => $salary],
                $employeeIndex,
            );

            if (($result['error'] ?? false) === true) {
                throw new RuntimeException('Default payroll seed failed for '.$employeeId.'.');
            }
        }
    }
}
