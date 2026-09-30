<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;

final class TransactionCashLedgerPaymentRowMapper
{
    /** @param array<string, string> $labels */
    public function map(Collection $rows, array $labels): Collection
    {
        return $rows->map(static fn (object $row): array => [
            'note_id' => (string) $row->note_id,
            'note_label' => $labels[TransactionCashLedgerPaymentNoteLabelResolver::key(
                (string) $row->note_id,
                (string) $row->customer_payment_id,
            )] ?? self::fallbackNoteLabel($row),
            'event_date' => (string) $row->event_date,
            'event_type' => 'payment_allocation',
            'direction' => 'in',
            'event_amount_rupiah' => (int) $row->event_amount_rupiah,
            'payment_method' => self::normalizePaymentMethod($row->payment_method ?? null),
            'cash_amount_paid_rupiah' => self::nullableInt($row->cash_amount_paid_rupiah ?? null),
            'cash_amount_received_rupiah' => self::nullableInt($row->cash_amount_received_rupiah ?? null),
            'cash_change_rupiah' => self::nullableInt($row->cash_change_rupiah ?? null),
            'customer_payment_id' => (string) $row->customer_payment_id,
            'refund_id' => null,
            'source_table' => (string) $row->source_table,
            'source_id' => (string) $row->customer_payment_id,
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

    private static function normalizePaymentMethod(mixed $value): string
    {
        $method = trim((string) ($value ?? ''));

        if ($method === 'tf') {
            return 'transfer';
        }

        return $method === '' ? 'unknown' : $method;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
