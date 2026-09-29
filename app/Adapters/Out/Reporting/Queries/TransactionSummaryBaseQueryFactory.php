<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionSummaryBaseQueryFactory
{
    public function __construct(
        private readonly TransactionSummaryRefundDueTotalsQuery $refundDueTotals,
        private readonly TransactionSummarySurplusRefundPaymentTotalsQuery $surplusRefundPaymentTotals,
        private readonly TransactionSummaryCashPaymentTotalsQuery $cashPaymentTotals,
        private readonly TransactionHistoricalNoteStateQuery $historicalNoteState,
    ) {}

    public function build(?string $from, ?string $to, string $mode): TransactionSummaryQueryContext
    {
        $cutoff = $mode === 'current' ? null : $to;
        $cashRefunds = DB::table('customer_refunds')
            ->when($cutoff !== null, fn (Builder $query) => $query->where('refunded_at', '<=', $cutoff))
            ->selectRaw('note_id, SUM(amount_rupiah) as refunded_rupiah')
            ->groupBy('note_id');

        $query = DB::table('notes')
            ->leftJoinSub($this->cashPaymentTotals->historicalPaymentTotals($cutoff), 'historical_payments', fn ($join) => $join->on('historical_payments.note_id', '=', 'notes.id'))
            ->leftJoinSub($this->cashPaymentTotals->query($cutoff), 'cash_payment_totals', fn ($join) => $join->on('cash_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashRefunds, 'cash_refund_totals', fn ($join) => $join->on('cash_refund_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($this->refundDueTotals->query($cutoff), 'refund_due_totals', fn ($join) => $join->on('refund_due_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($this->surplusRefundPaymentTotals->query($cutoff), 'surplus_refund_payment_totals', fn ($join) => $join->on('surplus_refund_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoin('note_history_projection', 'note_history_projection.note_id', '=', 'notes.id');

        $date = 'notes.transaction_date';
        $customer = 'notes.customer_name';
        $total = 'notes.total_rupiah';
        if ($cutoff !== null) {
            [$date, $customer, $total] = $this->applyHistoricalState($query, $cutoff);
        } else {
            $query->where('notes.note_state', '<>', 'cancelled');
        }
        if ($from !== null && $to !== null) {
            $query->whereRaw("{$date} BETWEEN ? AND ?", [$from, $to]);
        }

        return new TransactionSummaryQueryContext($query, $cutoff, $date, $customer, $total);
    }

    /** @return array{string,string,string} */
    private function applyHistoricalState(Builder $query, string $cutoff): array
    {
        $timestamp = $cutoff.' 23:59:59';
        $query->leftJoinSub(
            $this->historicalNoteState->latestRevisionNumbers($timestamp),
            'historical_revision_numbers',
            fn ($join) => $join->on('historical_revision_numbers.note_root_id', '=', 'notes.id'),
        )->leftJoin('note_revisions as historical_revision', function ($join): void {
            $join->on('historical_revision.note_root_id', '=', 'notes.id')
                ->on('historical_revision.revision_number', '=', 'historical_revision_numbers.revision_number');
        });
        $this->historicalNoteState->applyActiveAtCutoff($query, $timestamp);

        return [
            'COALESCE(historical_revision.transaction_date, notes.transaction_date)',
            'COALESCE(historical_revision.customer_name, notes.customer_name)',
            'COALESCE(historical_revision.grand_total_rupiah, notes.total_rupiah)',
        ];
    }
}
