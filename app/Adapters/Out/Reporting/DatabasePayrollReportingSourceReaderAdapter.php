<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\PayrollReportingSourceReaderPort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DatabasePayrollReportingSourceReaderAdapter implements PayrollReportingSourceReaderPort
{
    public function getPayrollReportRows(string $fromDate, string $toDate): array
    {
        return DB::table('payroll_disbursements')
            ->join('employees', 'employees.id', '=', 'payroll_disbursements.employee_id')
            ->leftJoin(
                'payroll_disbursement_reversals',
                'payroll_disbursements.id',
                '=',
                'payroll_disbursement_reversals.payroll_disbursement_id'
            )
            ->where(fn ($query) => $query->whereNull('payroll_disbursement_reversals.id')->orWhere('payroll_disbursement_reversals.created_at', '>', $toDate.' 23:59:59'))
            ->whereBetween(DB::raw('DATE(payroll_disbursements.disbursement_date)'), [$fromDate, $toDate])
            ->orderBy('payroll_disbursements.disbursement_date')
            ->orderBy('payroll_disbursements.id')
            ->get([
                'payroll_disbursements.id',
                'payroll_disbursements.employee_id',
                'employees.employee_name',
                'payroll_disbursements.amount',
                'payroll_disbursements.disbursement_date',
                'payroll_disbursements.mode',
                'payroll_disbursements.notes',
            ])
            ->map(function (object $row): array {
                return [
                    'id' => (string) $row->id,
                    'employee_id' => (string) $row->employee_id,
                    'employee_name' => (string) $row->employee_name,
                    'amount_rupiah' => (int) $row->amount,
                    'disbursement_date' => Carbon::parse((string) $row->disbursement_date)->format('Y-m-d'),
                    'mode_value' => (string) $row->mode,
                    'notes' => $row->notes !== null ? (string) $row->notes : null,
                ];
            })
            ->values()
            ->all();
    }

    public function getPayrollReportReconciliation(string $fromDate, string $toDate): array
    {
        $totals = DB::table('payroll_disbursements')
            ->leftJoin(
                'payroll_disbursement_reversals',
                'payroll_disbursements.id',
                '=',
                'payroll_disbursement_reversals.payroll_disbursement_id'
            )
            ->where(fn ($query) => $query->whereNull('payroll_disbursement_reversals.id')->orWhere('payroll_disbursement_reversals.created_at', '>', $toDate.' 23:59:59'))
            ->whereBetween(DB::raw('DATE(payroll_disbursements.disbursement_date)'), [$fromDate, $toDate])
            ->selectRaw('COUNT(*) as total_rows, COALESCE(SUM(payroll_disbursements.amount), 0) as total_amount_rupiah')
            ->first();

        return [
            'total_rows' => (int) ($totals->total_rows ?? 0),
            'total_amount_rupiah' => (int) ($totals->total_amount_rupiah ?? 0),
        ];
    }
}
