<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Facades\DB;

final class TransactionSummaryReportingQuery
{
    public function __construct(
        private readonly TransactionSummaryBaseQueryFactory $queries,
        private readonly TransactionSummarySqlExpressions $sql,
        private readonly TransactionSummaryDbRowMapper $mapper,
    ) {}

    public function rows(?string $fromTransactionDate, ?string $toTransactionDate, string $mode = 'as_of'): array
    {
        if (($fromTransactionDate === null) !== ($toTransactionDate === null)) {
            throw new \InvalidArgumentException('Transaction date bounds must both be supplied or both omitted.');
        }
        if (! in_array($mode, ['as_of', 'current'], true)) {
            throw new \InvalidArgumentException('Unknown transaction report mode.');
        }

        $context = $this->queries->build($fromTransactionDate, $toTransactionDate, $mode);
        return $context->query
            ->orderByRaw($context->effectiveDateSql)
            ->orderBy('notes.id')
            ->get([
                'notes.id as note_id',
                DB::raw($context->effectiveDateSql.' as transaction_date'),
                DB::raw($context->effectiveCustomerSql.' as customer_name'),
                DB::raw($context->effectiveTotalSql.' as gross_transaction_rupiah'),
                DB::raw($this->sql->allocated($context).' as allocated_payment_rupiah'),
                DB::raw('COALESCE(historical_payments.gross_payment_rupiah, 0) as gross_payment_rupiah'),
                DB::raw('COALESCE(cash_refund_totals.refunded_rupiah, 0) as refunded_rupiah'),
                DB::raw('COALESCE(refund_due_totals.refund_due_rupiah, 0) as refund_due_rupiah'),
                DB::raw('COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0) as surplus_refund_paid_rupiah'),
                DB::raw('GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0) - COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0), 0) as remaining_refund_due_rupiah'),
                DB::raw($this->sql->outstanding($context).' as outstanding_rupiah'),
            ])
            ->map(fn (object $row): array => $this->mapper->map($row))
            ->all();
    }

    public function reconciliation(string $fromTransactionDate, string $toTransactionDate): array
    {
        $rows = $this->rows($fromTransactionDate, $toTransactionDate);
        $sum = static fn (string $key): int => array_sum(array_column($rows, $key));
        return [
            'total_notes' => count($rows),
            'gross_transaction_rupiah' => $sum('gross_transaction_rupiah'),
            'allocated_payment_rupiah' => $sum('allocated_payment_rupiah'),
            'refunded_rupiah' => $sum('refunded_rupiah'),
            'refund_due_rupiah' => $sum('refund_due_rupiah'),
            'surplus_refund_paid_rupiah' => $sum('surplus_refund_paid_rupiah'),
            'remaining_refund_due_rupiah' => $sum('remaining_refund_due_rupiah'),
            'outstanding_rupiah' => $sum('outstanding_rupiah'),
        ];
    }
}
