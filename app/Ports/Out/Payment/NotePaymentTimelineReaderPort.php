<?php

declare(strict_types=1);

namespace App\Ports\Out\Payment;

interface NotePaymentTimelineReaderPort
{
    /**
     * Returns authoritative payment events in oldest-first order.
     *
     * @return list<array{
     *   payment_id:string,
     *   payment_amount_rupiah:int,
     *   allocated_amount_rupiah:int,
     *   payment_method:string,
     *   paid_at:string,
     *   occurred_at:string,
     *   amount_received_rupiah:?int,
     *   change_rupiah:?int
     * }>
     */
    public function findByNoteId(string $noteId): array;
}
