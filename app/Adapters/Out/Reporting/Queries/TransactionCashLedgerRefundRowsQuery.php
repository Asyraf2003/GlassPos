<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerRefundRowsQuery
{
    public function __construct(
        private readonly TransactionCashLedgerEventTimeNoteLabelResolver $noteLabels =
            new TransactionCashLedgerEventTimeNoteLabelResolver(),
    ) {}

    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        $rows = DB::table('customer_refunds')
            ->leftJoin('notes', 'notes.id', '=', 'customer_refunds.note_id')
            ->whereBetween('customer_refunds.refunded_at', [$fromEventDate, $toEventDate])
            ->orderBy('customer_refunds.refunded_at')
            ->orderBy('customer_refunds.id')
            ->get([
                'customer_refunds.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_refunds.refunded_at as event_date',
                'customer_refunds.created_at as event_created_at',
                'customer_refunds.amount_rupiah as event_amount_rupiah',
                'customer_refunds.customer_payment_id',
                'customer_refunds.id as refund_id',
            ]);

        $labels = $this->noteLabels->resolve($rows
            ->map(static function (object $row): array {
                $createdAt = trim((string) ($row->event_created_at ?? ''));

                return [
                    'key' => (string) $row->refund_id,
                    'note_id' => (string) $row->note_id,
                    'occurred_at' => $createdAt !== ''
                        ? $createdAt
                        : (string) $row->event_date.' 00:00:00',
                    'fallback_customer_name' => (string) ($row->customer_name ?? ''),
                    'fallback_transaction_date' => (string) ($row->transaction_date ?? ''),
                    'event_date' => (string) $row->event_date,
                ];
            })
            ->all());

        return $rows->map(static fn (object $row): array => [
            'note_id' => (string) $row->note_id,
            'note_label' => $labels[(string) $row->refund_id] ?? self::fallbackNoteLabel($row),
            'event_date' => (string) $row->event_date,
            'event_type' => 'refund',
            'direction' => 'out',
            'event_amount_rupiah' => (int) $row->event_amount_rupiah,
            'payment_method' => null,
            'cash_amount_paid_rupiah' => null,
            'cash_amount_received_rupiah' => null,
            'cash_change_rupiah' => null,
            'customer_payment_id' => (string) $row->customer_payment_id,
            'refund_id' => (string) $row->refund_id,
            'source_table' => 'customer_refunds',
            'source_id' => (string) $row->refund_id,
            'source_disposition_id' => null,
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
