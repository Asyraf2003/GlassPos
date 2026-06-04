<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment\Queries;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final class DatabaseNotePaymentAmountByNoteIdQuery
{
    public function total(string $noteId): Money
    {
        $normalizedNoteId = $this->normalize($noteId);

        $legacyPaymentIds = DB::table('payment_allocations')
            ->where('note_id', $normalizedNoteId)
            ->pluck('customer_payment_id');

        $componentPaymentIds = DB::table('payment_component_allocations')
            ->where('note_id', $normalizedNoteId)
            ->pluck('customer_payment_id');

        $paymentIds = $legacyPaymentIds
            ->merge($componentPaymentIds)
            ->filter(static fn (mixed $id): bool => trim((string) $id) !== '')
            ->unique()
            ->values();

        if ($paymentIds->isEmpty()) {
            return Money::zero();
        }

        return Money::fromInt((int) DB::table('customer_payments')
            ->whereIn('id', $paymentIds->all())
            ->sum('amount_rupiah'));
    }

    private function normalize(string $noteId): string
    {
        $normalized = trim($noteId);

        if ($normalized === '') {
            throw new DomainException('Note id pada payment allocation wajib ada.');
        }

        return $normalized;
    }
}
