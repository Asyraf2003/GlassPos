<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerPaymentRowsQuery
{
    public function __construct(
        private readonly TransactionCashLedgerLegacyPaymentAllocationRowsQuery $legacyPaymentRows =
            new TransactionCashLedgerLegacyPaymentAllocationRowsQuery(),
        private readonly TransactionCashLedgerComponentAllocationRowsQuery $componentPaymentRows =
            new TransactionCashLedgerComponentAllocationRowsQuery(),
        private readonly TransactionCashLedgerRefundedPaymentFallbackRowsQuery $refundedPaymentFallbackRows =
            new TransactionCashLedgerRefundedPaymentFallbackRowsQuery(),
        private readonly TransactionCashLedgerEventTimeNoteLabelResolver $noteLabels =
            new TransactionCashLedgerEventTimeNoteLabelResolver(),
    ) {}

    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        $rows = DB::query()
            ->fromSub(
                $this->legacyPaymentRows
                    ->query($fromEventDate, $toEventDate)
                    ->unionAll($this->componentPaymentRows->query($fromEventDate, $toEventDate))
                    ->unionAll($this->refundedPaymentFallbackRows->query($fromEventDate, $toEventDate)),
                'cash_payment_rows'
            )
            ->orderBy('event_date')
            ->orderBy('customer_payment_id')
            ->get([
                'note_id',
                'customer_name',
                'transaction_date',
                'event_date',
                'event_amount_rupiah',
                'customer_payment_id',
                'payment_method',
                'cash_amount_paid_rupiah',
                'cash_amount_received_rupiah',
                'cash_change_rupiah',
                'source_table',
            ]);

        $occurrences = $this->paymentOccurrences($rows);
        $labels = $this->noteLabels->resolve($rows
            ->map(static function (object $row) use ($occurrences): array {
                $paymentId = (string) $row->customer_payment_id;

                return [
                    'key' => self::labelKey((string) $row->note_id, $paymentId),
                    'note_id' => (string) $row->note_id,
                    'occurred_at' => $occurrences[$paymentId] ?? (string) $row->event_date.' 00:00:00',
                    'fallback_customer_name' => (string) ($row->customer_name ?? ''),
                    'fallback_transaction_date' => (string) ($row->transaction_date ?? ''),
                    'event_date' => (string) $row->event_date,
                ];
            })
            ->all());

        return $rows->map(static fn (object $row): array => [
            'note_id' => (string) $row->note_id,
            'note_label' => $labels[self::labelKey(
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

    /** @return array<string, string> */
    private function paymentOccurrences(Collection $rows): array
    {
        $paymentIds = $rows->pluck('customer_payment_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($paymentIds === []) {
            return [];
        }

        return DB::table('customer_payments')
            ->whereIn('id', $paymentIds)
            ->get(['id', 'paid_at', 'recorded_at', 'created_at'])
            ->mapWithKeys(static function (object $row): array {
                $recordedAt = trim((string) ($row->recorded_at ?? ''));
                $createdAt = trim((string) ($row->created_at ?? ''));
                $paidAt = (string) $row->paid_at;

                return [(string) $row->id => $recordedAt !== ''
                    ? $recordedAt
                    : ($createdAt !== '' ? $createdAt : $paidAt.' 00:00:00')];
            })
            ->all();
    }

    private static function labelKey(string $noteId, string $paymentId): string
    {
        return $noteId.'|'.$paymentId;
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
