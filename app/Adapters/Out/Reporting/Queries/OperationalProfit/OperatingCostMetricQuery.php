<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\OperationalProfit;

use App\Adapters\Out\Reporting\EmployeeDebtDisbursementQuery;
use Illuminate\Support\Facades\DB;

final class OperatingCostMetricQuery
{
    public function operationalExpense(string $fromDate, string $toDate): int
    {
        return (int) (DB::table('operational_expenses')
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $toDate.' 23:59:59'))
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->sum('amount_rupiah') ?? 0);
    }

    public function payrollDisbursement(string $fromDate, string $toDate): int
    {
        return (int) (DB::table('payroll_disbursements')
            ->leftJoin(
                'payroll_disbursement_reversals',
                'payroll_disbursements.id',
                '=',
                'payroll_disbursement_reversals.payroll_disbursement_id'
            )
            ->where(fn ($query) => $query->whereNull('payroll_disbursement_reversals.id')->orWhere('payroll_disbursement_reversals.created_at', '>', $toDate.' 23:59:59'))
            ->whereBetween('payroll_disbursements.disbursement_date', [
                $this->startOfDay($fromDate),
                $this->endOfDay($toDate),
            ])
            ->sum('payroll_disbursements.amount') ?? 0);
    }

    public function employeeDebtCashOut(string $fromDate, string $toDate): int
    {
        return array_sum(array_column(
            (new EmployeeDebtDisbursementQuery)->daily($fromDate, $toDate),
            'amount_rupiah',
        ));
    }

    private function startOfDay(string $date): string
    {
        return $date.' 00:00:00';
    }

    private function endOfDay(string $date): string
    {
        return $date.' 23:59:59';
    }
}
