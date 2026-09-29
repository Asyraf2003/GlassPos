<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EmployeeDebtTemporalQuery
{
    public function rows(string $from, string $to): array
    {
        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        return DB::table('employee_debts as d')
            ->leftJoinSub($this->adjustments($start, $end), 'a', 'a.employee_debt_id', '=', 'd.id')
            ->leftJoinSub($this->payments($start, $end), 'p', 'p.employee_debt_id', '=', 'd.id')
            ->where('d.created_at', '<=', $end)
            ->orderBy('d.created_at')->orderBy('d.id')
            ->get(['d.*', 'a.all_adjustments', 'a.opening_adjustments', 'a.closing_adjustments',
                'p.opening_paid', 'p.closing_paid', 'p.period_payments', 'p.period_reversals'])
            ->map(static fn (object $row): array => EmployeeDebtTemporalRowMapper::map($row, $start))
            ->all();
    }

    private function adjustments(string $start, string $end): Builder
    {
        // Before/after snapshots preserve signed corrections, including legacy decreases.
        $delta = '(after_total_debt - before_total_debt)';

        return DB::table('employee_debt_adjustments')
            ->select('employee_debt_id')->groupBy('employee_debt_id')
            ->selectRaw("SUM({$delta}) as all_adjustments")
            ->selectRaw("SUM(CASE WHEN created_at < ? THEN {$delta} ELSE 0 END) as opening_adjustments", [$start])
            ->selectRaw("SUM(CASE WHEN created_at <= ? THEN {$delta} ELSE 0 END) as closing_adjustments", [$end]);
    }

    private function payments(string $start, string $end): Builder
    {
        return DB::table('employee_debt_payments as p')
            ->leftJoin('employee_debt_payment_reversals as r', 'r.employee_debt_payment_id', '=', 'p.id')
            ->select('p.employee_debt_id')->groupBy('p.employee_debt_id')
            ->selectRaw('SUM(CASE WHEN p.payment_date < ? AND (r.created_at IS NULL OR r.created_at >= ?) THEN p.amount ELSE 0 END) as opening_paid', [$start, $start])
            ->selectRaw('SUM(CASE WHEN p.payment_date <= ? AND (r.created_at IS NULL OR r.created_at > ?) THEN p.amount ELSE 0 END) as closing_paid', [$end, $end])
            ->selectRaw('SUM(CASE WHEN p.payment_date BETWEEN ? AND ? THEN p.amount ELSE 0 END) as period_payments', [$start, $end])
            ->selectRaw('SUM(CASE WHEN r.created_at BETWEEN ? AND ? AND p.payment_date <= ? THEN p.amount ELSE 0 END) as period_reversals', [$start, $end, $end]);
    }
}
