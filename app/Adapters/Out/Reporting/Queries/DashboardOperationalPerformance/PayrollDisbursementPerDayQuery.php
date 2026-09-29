<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardOperationalPerformance;

use Illuminate\Support\Facades\DB;

final class PayrollDisbursementPerDayQuery
{
    /**
     * @return list<array{
     *   period_key:string,
     *   period_label:string,
     *   amount_rupiah:int
     * }>
     */
    public function rows(string $fromDate, string $toDate): array
    {
        return DB::table('payroll_disbursements')
            ->leftJoin(
                'payroll_disbursement_reversals',
                'payroll_disbursements.id',
                '=',
                'payroll_disbursement_reversals.payroll_disbursement_id',
            )
            ->where(fn ($query) => $query->whereNull('payroll_disbursement_reversals.id')->orWhere('payroll_disbursement_reversals.created_at', '>', $toDate.' 23:59:59'))
            ->whereBetween('payroll_disbursements.disbursement_date', [
                $this->startOfDay($fromDate),
                $this->endOfDay($toDate),
            ])
            ->selectRaw(
                'DATE(payroll_disbursements.disbursement_date) as period_key, '.
                'COALESCE(SUM(payroll_disbursements.amount), 0) as amount_rupiah'
            )
            ->groupBy(DB::raw('DATE(payroll_disbursements.disbursement_date)'))
            ->orderBy(DB::raw('DATE(payroll_disbursements.disbursement_date)'))
            ->get()
            ->map(static fn (object $row): array => [
                'period_key' => (string) $row->period_key,
                'period_label' => (string) $row->period_key,
                'amount_rupiah' => (int) $row->amount_rupiah,
            ])
            ->values()
            ->all();
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
