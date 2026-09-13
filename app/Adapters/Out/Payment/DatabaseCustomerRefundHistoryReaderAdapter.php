<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment;

use App\Core\Payment\CustomerRefund\CustomerRefund;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundHistoryReaderPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DatabaseCustomerRefundHistoryReaderAdapter implements CustomerRefundHistoryReaderPort
{
    public function listByNoteId(string $noteId): array
    {
        $id = trim($noteId);

        if ($id === '') {
            throw new DomainException('Note id pada refund history wajib ada.');
        }

        return DB::table('customer_refunds')
            ->where('note_id', $id)
            ->orderByDesc('refunded_at')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (object $row): CustomerRefund => CustomerRefund::rehydrate(
                (string) $row->id,
                (string) $row->customer_payment_id,
                (string) $row->note_id,
                Money::fromInt((int) $row->amount_rupiah),
                new DateTimeImmutable((string) $row->refunded_at),
                (string) $row->reason,
            ))
            ->all();
    }
}
