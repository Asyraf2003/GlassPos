<?php

declare(strict_types=1);

namespace App\Adapters\Out\Expense\Concerns;

use App\Application\Expense\DTO\ExpenseTableQuery;
use Illuminate\Database\Query\Builder;

trait OperationalExpenseTableOrdering
{
    private function applyTableSorting(Builder $builder, ExpenseTableQuery $query): Builder
    {
        $sortable = ['expense_date', 'category_name_snapshot', 'description', 'amount_rupiah', 'payment_method'];
        $sortDir = $query->sortDir() === 'asc' ? 'asc' : 'desc';

        if ($query->sortBy() === 'relevance' && $query->q() !== null) {
            $keyword = mb_strtolower($query->q());

            return $builder
                ->orderByRaw(
                    'CASE
                        WHEN LOWER(category_code_snapshot) = ? THEN 0
                        WHEN LOWER(category_name_snapshot) = ? THEN 1
                        WHEN LOWER(category_code_snapshot) LIKE ? THEN 2
                        WHEN LOWER(category_name_snapshot) LIKE ? THEN 3
                        WHEN LOWER(description) LIKE ? THEN 4
                        WHEN LOWER(payment_method) LIKE ? THEN 5
                        ELSE 6
                    END',
                    [$keyword, $keyword, $keyword.'%', $keyword.'%', '%'.$keyword.'%', '%'.$keyword.'%'],
                )
                ->orderByDesc('expense_date')
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        }

        $sortBy = in_array($query->sortBy(), $sortable, true) ? $query->sortBy() : 'expense_date';

        return $builder
            ->orderBy($sortBy, $sortDir)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
