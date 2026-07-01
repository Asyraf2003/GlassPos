<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerSurplusRefundPaidRowsQuery
{
    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        return DB::table('note_revision_surplus_refund_payments')
            ->leftJoin('notes', 'notes.id', '=', 'note_revision_surplus_refund_payments.note_root_id')
            ->where('note_revision_surplus_refund_payments.status', 'active')
            ->whereBetween('note_revision_surplus_refund_payments.effective_date', [$fromEventDate, $toEventDate])
            ->orderBy('note_revision_surplus_refund_payments.effective_date')
            ->orderBy('note_revision_surplus_refund_payments.id')
            ->get([
                'note_revision_surplus_refund_payments.note_root_id as note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'note_revision_surplus_refund_payments.effective_date as event_date',
                'note_revision_surplus_refund_payments.amount_rupiah as event_amount_rupiah',
                'note_revision_surplus_refund_payments.id as surplus_refund_payment_id',
                'note_revision_surplus_refund_payments.note_revision_surplus_disposition_id as source_disposition_id',
            ])
            ->map(static fn (object $row): array => [
                'note_id' => (string) $row->note_id,
                'note_label' => trim((string) ($row->customer_name ?? '')) !== ''
                    ? (string) $row->customer_name . ' · ' . (string) ($row->transaction_date ?? $row->event_date)
                    : 'Nota ' . (string) ($row->transaction_date ?? $row->event_date),
                'event_date' => (string) $row->event_date,
                'event_type' => 'surplus_refund_paid',
                'direction' => 'out',
                'event_amount_rupiah' => (int) $row->event_amount_rupiah,
                'payment_method' => null,
                'cash_amount_paid_rupiah' => null,
                'cash_amount_received_rupiah' => null,
                'cash_change_rupiah' => null,
                'customer_payment_id' => null,
                'refund_id' => null,
                'surplus_refund_payment_id' => (string) $row->surplus_refund_payment_id,
                'source_table' => 'note_revision_surplus_refund_payments',
                'source_id' => (string) $row->surplus_refund_payment_id,
                'source_disposition_id' => (string) $row->source_disposition_id,
            ]);
    }
}
