<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

final class TransactionSummaryDbRowMapper
{
    /** @return array<string, int|string> */
    public function map(object $row): array
    {
        return [
            'note_id' => (string) $row->note_id,
            'transaction_date' => (string) $row->transaction_date,
            'customer_name' => (string) $row->customer_name,
            'gross_transaction_rupiah' => (int) $row->gross_transaction_rupiah,
            'allocated_payment_rupiah' => (int) $row->allocated_payment_rupiah,
            'gross_payment_rupiah' => (int) $row->gross_payment_rupiah,
            'refunded_rupiah' => (int) $row->refunded_rupiah,
            'refund_due_rupiah' => (int) $row->refund_due_rupiah,
            'surplus_refund_paid_rupiah' => (int) $row->surplus_refund_paid_rupiah,
            'remaining_refund_due_rupiah' => (int) $row->remaining_refund_due_rupiah,
            'outstanding_rupiah' => (int) $row->outstanding_rupiah,
        ];
    }
}
