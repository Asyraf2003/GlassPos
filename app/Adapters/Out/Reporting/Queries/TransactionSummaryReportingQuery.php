<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Facades\DB;

final class TransactionSummaryReportingQuery
{
    public function __construct(
        private readonly TransactionSummaryRefundDueTotalsQuery $refundDueTotals,
        private readonly TransactionSummarySurplusRefundPaymentTotalsQuery $surplusRefundPaymentTotals,
        private readonly TransactionSummaryCashPaymentTotalsQuery $cashPaymentTotals,
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
            ->when($cutoff !== null, fn ($query) => $query->where('refunded_at', '<=', $cutoff))
            ->selectRaw('note_id, SUM(amount_rupiah) as refunded_rupiah')
            ->groupBy('note_id');
        $refundDueTotals = $this->refundDueTotals->query($cutoff);
        $surplusRefundPaymentTotals = $this->surplusRefundPaymentTotals->query($cutoff);

        return DB::table('notes')
            ->leftJoinSub($this->cashPaymentTotals->historicalPaymentTotals($cutoff), 'historical_payments', fn ($join) => $join->on('historical_payments.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashPaymentTotals, 'cash_payment_totals', fn ($join) => $join->on('cash_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashRefundTotals, 'cash_refund_totals', fn ($join) => $join->on('cash_refund_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($refundDueTotals, 'refund_due_totals', fn ($join) => $join->on('refund_due_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($surplusRefundPaymentTotals, 'surplus_refund_payment_totals', fn ($join) => $join->on('surplus_refund_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoin('note_history_projection', 'note_history_projection.note_id', '=', 'notes.id')
            ->when($fromTransactionDate !== null && $toTransactionDate !== null,
                fn ($query) => $query->whereBetween('notes.transaction_date', [$fromTransactionDate, $toTransactionDate]))
            ->where('notes.note_state', '<>', 'cancelled')
            ->orderBy('notes.transaction_date')
            ->orderBy('notes.id')
            ->get([
                'notes.id as note_id',
                'notes.transaction_date',
                'notes.customer_name',
                'notes.total_rupiah as gross_transaction_rupiah',
                DB::raw('COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) as allocated_payment_rupiah'),
                DB::raw('COALESCE(historical_payments.gross_payment_rupiah, 0) as gross_payment_rupiah'),
                DB::raw('COALESCE(cash_refund_totals.refunded_rupiah, 0) as refunded_rupiah'),
                DB::raw('COALESCE(refund_due_totals.refund_due_rupiah, 0) as refund_due_rupiah'),
                DB::raw('COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0) as surplus_refund_paid_rupiah'),
                DB::raw('GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0) - COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0), 0) as remaining_refund_due_rupiah'),
                DB::raw($cutoff !== null ? $this->historicalOutstandingSql() : 'CASE WHEN COALESCE(cash_refund_totals.refunded_rupiah, 0) > 0 THEN COALESCE(note_history_projection.outstanding_rupiah, GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) + COALESCE(cash_refund_totals.refunded_rupiah, 0), 0)) ELSE GREATEST(notes.total_rupiah - COALESCE(cash_payment_totals.allocated_payment_rupiah, 0), 0) END as outstanding_rupiah'),
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

    private function historicalOutstandingSql(): string
    {
        // Versioned settlement follows BuildCreateNoteRevisionSettlement:
        // gross linked cash survives allocation rebuilding; committed surplus
        // reduces available carry-forward even before its cash refund is paid.
        // Legacy reports retain their allocation basis, without current projection.
        return 'GREATEST(notes.total_rupiah - GREATEST('
            .'CASE WHEN notes.current_revision_id IS NOT NULL THEN GREATEST('
            .'COALESCE(historical_payments.gross_payment_rupiah, 0), COALESCE(cash_payment_totals.allocated_payment_rupiah, 0)) '
            .'ELSE COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) END '
            .'- COALESCE(cash_refund_totals.refunded_rupiah, 0) '
            .'- GREATEST(COALESCE(refund_due_totals.refund_due_rupiah, 0), COALESCE(surplus_refund_payment_totals.surplus_refund_paid_rupiah, 0)), 0), 0) as outstanding_rupiah';
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
