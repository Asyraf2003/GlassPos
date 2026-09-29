<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\OperationalExpenseReportingSourceReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseOperationalExpenseReportingSourceReaderAdapter implements OperationalExpenseReportingSourceReaderPort
{
    public function getOperationalExpenseSummaryRows(
        string $fromExpenseDate,
        string $toExpenseDate,
    ): array {
        return DB::table('operational_expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'operational_expenses.category_id')
            ->where(fn ($query) => $query->whereNull('operational_expenses.deleted_at')->orWhere('operational_expenses.deleted_at', '>', $toExpenseDate.' 23:59:59'))
            ->whereBetween('operational_expenses.expense_date', [$fromExpenseDate, $toExpenseDate])
            ->orderBy('operational_expenses.expense_date')
            ->orderBy('operational_expenses.id')
            ->get([
                'operational_expenses.id as expense_id',
                'operational_expenses.expense_date',
                'operational_expenses.category_id',
                'expense_categories.code as category_code',
                'expense_categories.name as category_name',
                'operational_expenses.amount_rupiah',
                'operational_expenses.description',
                'operational_expenses.payment_method',
                'operational_expenses.reference_no',
            ])
            ->map(static fn (object $row): array => [
                'expense_id' => (string) $row->expense_id,
                'expense_date' => (string) $row->expense_date,
                'category_id' => (string) $row->category_id,
                'category_code' => (string) $row->category_code,
                'category_name' => (string) $row->category_name,
                'amount_rupiah' => (int) $row->amount_rupiah,
                'description' => (string) $row->description,
                'payment_method' => (string) $row->payment_method,
                'reference_no' => $row->reference_no !== null ? (string) $row->reference_no : null,
            ])
            ->values()
            ->all();
    }

    public function getOperationalExpenseSummaryReconciliation(
        string $fromExpenseDate,
        string $toExpenseDate,
    ): array {
        $totals = DB::table('operational_expenses')
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $toExpenseDate.' 23:59:59'))
            ->whereBetween('expense_date', [$fromExpenseDate, $toExpenseDate])
            ->selectRaw('COUNT(*) as total_rows, COALESCE(SUM(amount_rupiah), 0) as total_amount_rupiah')
            ->first();

        return [
            'total_rows' => (int) ($totals->total_rows ?? 0),
            'total_amount_rupiah' => (int) ($totals->total_amount_rupiah ?? 0),
        ];
    }
}
