<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EmployeeDebtDisbursementQuery
{
    public function daily(string $from, string $to): array
    {
        return DB::query()->fromSub($this->events(), 'events')
            ->whereBetween('event_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('DATE(event_at) as period_key, SUM(amount_rupiah) as amount_rupiah')
            ->groupByRaw('DATE(event_at)')->orderBy('period_key')->get()
            ->map(static fn (object $row): array => [
                'period_key' => (string) $row->period_key,
                'period_label' => (string) $row->period_key,
                'amount_rupiah' => (int) $row->amount_rupiah,
            ])->all();
    }

    private function events(): Builder
    {
        $adjustments = DB::table('employee_debt_adjustments')->select('employee_debt_id')
            ->selectRaw('SUM(after_total_debt - before_total_debt) as delta')->groupBy('employee_debt_id');
        $initial = DB::table('employee_debts as d')
            ->leftJoinSub($adjustments, 'a', 'a.employee_debt_id', '=', 'd.id')
            ->selectRaw('d.created_at as event_at, d.total_debt - COALESCE(a.delta, 0) as amount_rupiah');
        $increases = DB::table('employee_debt_adjustments')->where('adjustment_type', 'increase')
            ->selectRaw('created_at as event_at, after_total_debt - before_total_debt as amount_rupiah');

        return $initial->unionAll($increases);
    }
}
