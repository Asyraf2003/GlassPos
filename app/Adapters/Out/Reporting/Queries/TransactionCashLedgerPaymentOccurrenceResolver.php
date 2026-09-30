<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerPaymentOccurrenceResolver
{
    /** @return array<string, string> */
    public function resolve(Collection $rows): array
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
}
