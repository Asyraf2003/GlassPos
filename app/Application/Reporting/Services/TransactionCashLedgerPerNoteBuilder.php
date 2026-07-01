<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

use App\Application\Reporting\DTO\TransactionCashLedgerPerNoteRow;

final class TransactionCashLedgerPerNoteBuilder
{
    /**
     * @param list<array{
     *   note_id:string,
     *   event_date:string,
     *   event_type:string,
     *   direction:string,
     *   event_amount_rupiah:int,
     *   payment_method?:?string,
     *   cash_amount_paid_rupiah?:?int,
     *   cash_amount_received_rupiah?:?int,
     *   cash_change_rupiah?:?int,
     *   customer_payment_id:?string,
     *   refund_id:?string,
     *   source_table:string,
     *   source_id:string,
     *   source_disposition_id:?string
     * }> $rows
     * @return list<TransactionCashLedgerPerNoteRow>
     */
    public function build(array $rows): array
    {
        return array_map(
            static fn (array $row): TransactionCashLedgerPerNoteRow => new TransactionCashLedgerPerNoteRow(
                $row['note_id'],
                $row['event_date'],
                $row['event_type'],
                $row['direction'],
                $row['event_amount_rupiah'],
                isset($row['payment_method']) ? (string) $row['payment_method'] : null,
                self::nullableInt($row['cash_amount_paid_rupiah'] ?? null),
                self::nullableInt($row['cash_amount_received_rupiah'] ?? null),
                self::nullableInt($row['cash_change_rupiah'] ?? null),
                $row['customer_payment_id'],
                $row['refund_id'],
                $row['source_table'],
                $row['source_id'],
                $row['source_disposition_id'],
            ),
            $rows,
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
