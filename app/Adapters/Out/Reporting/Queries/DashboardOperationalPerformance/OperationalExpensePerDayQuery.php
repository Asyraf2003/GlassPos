<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardOperationalPerformance;

use Illuminate\Support\Facades\DB;

final class OperationalExpensePerDayQuery
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
        return DB::table('operational_expenses')
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $toDate.' 23:59:59'))
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->selectRaw('expense_date as period_key, COALESCE(SUM(amount_rupiah), 0) as amount_rupiah')
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get()
            ->map(static fn (object $row): array => [
                'period_key' => (string) $row->period_key,
                'period_label' => (string) $row->period_key,
                'amount_rupiah' => (int) $row->amount_rupiah,
            ])
            ->values()
            ->all();
    }
}
