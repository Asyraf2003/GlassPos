<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionSummaryReportingQuery
{
    public function __construct(
        private readonly TransactionSummaryRefundDueTotalsQuery $refundDueTotals,
        private readonly TransactionSummarySurplusRefundPaymentTotalsQuery $surplusRefundPaymentTotals,
        private readonly TransactionSummaryCashPaymentTotalsQuery $cashPaymentTotals,
        private readonly TransactionHistoricalNoteStateQuery $historicalNoteState,
    ) {}

    public function rows(?string $fromTransactionDate, ?string $toTransactionDate, string $mode = 'as_of'): array
    {
        if (($fromTransactionDate === null) !== ($toTransactionDate === null)) {
            throw new \InvalidArgumentException('Transaction date bounds must both be supplied or both omitted.');
        }

        if (! in_array($mode, ['as_of', 'current'], true)) {
            throw new \InvalidArgumentException('Unknown transaction report mode.');
        }

        $cutoff = $mode === 'current' ? null : $toTransactionDate;
        $cashPaymentTotals = $this->cashPaymentTotals->query($cutoff);
        $cashRefundTotals = DB::table('customer_refunds')
            ->when($cutoff !== null, fn (Builder $query) => $query->where('refunded_at', '<=', $cutoff))
            ->selectRaw('note_id, SUM(amount_rupiah) as refunded_rupiah')
            ->groupBy('note_id');
        $refundDueTotals = $this->refundDueTotals->query($cutoff);
        $surplusRefundPaymentTotals = $this->surplusRefundPaymentTotals->query($cutoff);

        $query = DB::table('notes')
            ->leftJoinSub($this->cashPaymentTotals->historicalPaymentTotals($cutoff), 'historical_payments', fn ($join) => $join->on('historical_payments.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashPaymentTotals, 'cash_payment_totals', fn ($join) => $join->on('cash_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashRefundTotals, 'cash_refund_totals', fn ($join) => $join->on('cash_refund_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($refundDueTotals, 'refund_due_totals', fn ($join) => $join->on('refund_due_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($surplusRefundPaymentTotals, 'surplus_refund_payment_totals', fn ($join) => $join->on('surplus_refund_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoin('note_history_projection', 'note_history_projection.note_id', '=', 'notes.id');

        $effectiveDateSql = 'notes.transaction_date';
        $effectiveCustomerSql = 'notes.customer_name';
        $effectiveTotalSql = 'notes.total_rupiah';

        if ($cutoff !== null) {
            $cutoffTimestamp = $cutoff.' 23:59:59';
            $query->leftJoinSub(
                $this->historicalNoteState->latestRevisionNumbers($cutoffTimestamp),
                'historical_revision_numbers',
                fn ($join) => $join->on('historical_revision_numbers.note_root_id', '=', 'notes.id'),
            )->leftJoin('note_revisions as historical_revision', function ($join): void {
                $join->on('historical_revision.note_root_id', '=', 'notes.id')
                    ->on('historical_revision.revision_number', '=', 'historical_revision_numbers.revision_number');
            });
            $this->historicalNoteState->applyActiveAtCutoff($query, $cutoffTimestamp);
            $effectiveDateSql = 'COALESCE(historical_revision.transaction_date, notes.transaction_date)';
            $effectiveCustomerSql = 'COALESCE(historical_revision.customer_name, notes.customer_name)';
            $effectiveTotalSql = 'COALESCE(historical_revision.grand_total_rupiah, notes.total_rupiah)';
        } else {
            $query->where('notes.note_state', '<>', 'cancelled');
        }

        if ($fromTransactionDate !== null && $toTransactionDate !== null) {
            $query->whereRaw("{$effectiveDateSql} BETWEEN ? AND ?", [$fromTransactionDate, $toTransactionDate]);
        }

        $allocatedSql = $cutoff !== null
            ? $this->historicalAllocatedSql($effectiveTotalSql)
            : 'COALESCE(cash_payment_totals.allocated_payment_rupiah, 0)';
        $outstandingSql = $cutoff !== null
            ? $this->historicalOutstandingSql($effectiveTotalSql)
            : 'CASE WHEN COALESCE(cash_refund_totals.refunded_rupiah, 0) > 0 '
                .'THEN COALESCE(note_history_projection.outstanding_rupiah, GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) + COALESCE(cash_refund_totals.refunded_rupiah, 0), 0)) '
                .'ELSE GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0), 0) END';

        return $query
            ->orderByRaw($effectiveDateSql)
            ->orderBy('notes.id')
            ->get([
                'notes.id as note_id',
                DB::raw($effectiveDateSql.' as transaction_date'),
                DB::raw($effectiveCustomerSql.' as customer_name'),
                DB::raw($effectiveTotalSql.' as gross_transaction_rupiah'),
                DB::raw($allocatedSql.' as allocated_payment_rupiah'),
                DB::raw('COALESCE(historical_payments.gross_payment_rupiah, 0) as gross_payment_rupiah'),
                DB::raw('COALESCE(cash_refund_totals.refunded_rupiah, 0) as refunded_rupiah'),
                DB::raw('COALESCE(refund_due_totals.refund_due_rupiah, 0) as refund_due_rupiah'),
                DB::raw('COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0) as surplus_refund_paid_rupiah'),
                DB::raw('GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0) - COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0), 0) as remaining_refund_due_rupiah'),
                DB::raw($outstandingSql.' as outstanding_rupiah'),
            ])
            ->map(static fn (object $row): array => [
                'note_id' => (string) $row->note_id,
                'transaction_date' => (string) $row->transaction_date,
                'customer_name' => (string) $row->customer_name,
                'gross_transaction_rupiah' => (int) $row->gross_transaction_rupiah,
                'allocated_payment_rupiah' => (int) $row->allocated_payment_rupiah,
                'gross_payment_rupiah' => (int) $row->gross_payment_rupiah,
                'refunded_rupiah' => (int) $row->refunded_rupiah,
                'refund_due_rupiah' => (int) $row->refund_due_rupiah,
                'surplus_refund_paid_rupiah' => (int) $row->surplus_refund_paid_rupiah,
                'remaining_refund_due_rupiah' => (int) $row->remaining_refund_due_rupiah,
                'outstanding_rupiah' => (int) $row->outstanding_rupiah,
            ])
            ->all();
    }

    private function historicalAllocatedSql(string $effectiveTotalSql): string
    {
        return 'CASE WHEN historical_revision.id IS NOT NULL '
            .'THEN LEAST('.$effectiveTotalSql.', '.$this->settlementAvailableSql().') '
            .'ELSE COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) END';
    }

    private function historicalOutstandingSql(string $effectiveTotalSql): string
    {
        $legacyAvailable = 'GREATEST(COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) '
            .'- COALESCE(cash_refund_totals.refunded_rupiah, 0) '
            .'- GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0), COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0)), 0)';

        return 'CASE WHEN historical_revision.id IS NOT NULL '
            .'THEN GREATEST('.$effectiveTotalSql.' - '.$this->settlementAvailableSql().', 0) '
            .'ELSE GREATEST('.$effectiveTotalSql.' - '.$legacyAvailable.', 0) END';
    }

    private function settlementAvailableSql(): string
    {
        return 'GREATEST(COALESCE(historical_payments.gross_payment_rupiah, 0) '
            .'- COALESCE(cash_refund_totals.refunded_rupiah, 0) '
            .'- GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0), COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0)), 0)';
    }

    public function reconciliation(string $fromTransactionDate, string $toTransactionDate): array
    {
        $rows = $this->rows($fromTransactionDate, $toTransactionDate);

        return [
            'total_notes' => count($rows),
            'gross_transaction_rupiah' => array_sum(array_column($rows, 'gross_transaction_rupiah')),
            'allocated_payment_rupiah' => array_sum(array_column($rows, 'allocated_payment_rupiah')),
            'refunded_rupiah' => array_sum(array_column($rows, 'refunded_rupiah')),
            'refund_due_rupiah' => array_sum(array_column($rows, 'refund_due_rupiah')),
            'surplus_refund_paid_rupiah' => array_sum(array_column($rows, 'surplus_refund_paid_rupiah')),
            'remaining_refund_due_rupiah' => array_sum(array_column($rows, 'remaining_refund_due_rupiah')),
            'outstanding_rupiah' => array_sum(array_column($rows, 'outstanding_rupiah')),
        ];
    }
}
