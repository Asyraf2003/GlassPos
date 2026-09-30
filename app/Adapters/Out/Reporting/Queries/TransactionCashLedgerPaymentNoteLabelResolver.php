<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;

final class TransactionCashLedgerPaymentNoteLabelResolver
{
    public function __construct(
        private readonly TransactionCashLedgerPaymentOccurrenceResolver $occurrences =
            new TransactionCashLedgerPaymentOccurrenceResolver(),
        private readonly TransactionCashLedgerEventTimeNoteLabelResolver $eventTimeLabels =
            new TransactionCashLedgerEventTimeNoteLabelResolver(),
    ) {}

    /** @return array<string, string> */
    public function resolve(Collection $rows): array
    {
        $occurrences = $this->occurrences->resolve($rows);
        $events = $rows->map(static function (object $row) use ($occurrences): array {
            $paymentId = (string) $row->customer_payment_id;

            return [
                'key' => self::key((string) $row->note_id, $paymentId),
                'note_id' => (string) $row->note_id,
                'occurred_at' => $occurrences[$paymentId] ?? (string) $row->event_date.' 00:00:00',
                'fallback_customer_name' => (string) ($row->customer_name ?? ''),
                'fallback_transaction_date' => (string) ($row->transaction_date ?? ''),
                'event_date' => (string) $row->event_date,
            ];
        })->all();

        return $this->eventTimeLabels->resolve($events);
    }

    public static function key(string $noteId, string $paymentId): string
    {
        return $noteId.'|'.$paymentId;
    }
}
