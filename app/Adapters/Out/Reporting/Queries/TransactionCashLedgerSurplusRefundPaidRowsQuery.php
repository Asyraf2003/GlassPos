<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerSurplusRefundPaidRowsQuery
{
    public function __construct(
        private readonly TransactionCashLedgerEventTimeNoteLabelResolver $noteLabels =
            new TransactionCashLedgerEventTimeNoteLabelResolver(),
    ) {}

    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        $rows = DB::table('note_revision_surplus_refund_payments')
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
                'note_revision_surplus_refund_payments.occurred_at as event_occurred_at',
                'note_revision_surplus_refund_payments.amount_rupiah as event_amount_rupiah',
                'note_revision_surplus_refund_payments.id as surplus_refund_payment_id',
                'note_revision_surplus_refund_payments.note_revision_surplus_disposition_id as source_disposition_id',
            ]);

        $labels = $this->noteLabels->resolve($rows
            ->map(static function (object $row): array {
                $occurredAt = trim((string) ($row->event_occurred_at ?? ''));

                return [
                    'key' => (string) $row->surplus_refund_payment_id,
                    'note_id' => (string) $row->note_id,
                    'occurred_at' => $occurredAt !== ''
                        ? $occurredAt
                        : (string) $row->event_date.' 00:00:00',
                    'fallback_customer_name' => (string) ($row->customer_name ?? ''),
                    'fallback_transaction_date' => (string) ($row->transaction_date ?? ''),
                    'event_date' => (string) $row->event_date,
                ];
            })
            ->all());

        return $rows->map(static fn (object $row): array => [
            'note_id' => (string) $row->note_id,
            'note_label' => $labels[(string) $row->surplus_refund_payment_id] ?? self::fallbackNoteLabel($row),
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

    private static function fallbackNoteLabel(object $row): string
    {
        $customerName = trim((string) ($row->customer_name ?? ''));
        $date = (string) ($row->transaction_date ?? $row->event_date);

        return $customerName !== ''
            ? $customerName.' · '.$date
            : 'Nota '.$date;
    }
}
