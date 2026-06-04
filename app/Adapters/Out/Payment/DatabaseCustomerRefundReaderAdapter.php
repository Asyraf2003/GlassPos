<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseCustomerRefundReaderAdapter implements CustomerRefundReaderPort
{
    public function getTotalRefundedAmountByNoteId(string $noteId): Money
    {
        $normalizedNoteId = $this->normalize($noteId, 'Note id pada customer refund wajib ada.');
        $componentTotal = (int) DB::table('refund_component_allocations')
            ->where('note_id', $normalizedNoteId)
            ->sum('refunded_amount_rupiah');

        $componentRefundIds = DB::table('refund_component_allocations')
            ->where('note_id', $normalizedNoteId)
            ->select('customer_refund_id');

        $legacyTotal = (int) DB::table('customer_refunds')
            ->where('note_id', $normalizedNoteId)
            ->whereNotIn('id', $componentRefundIds)
            ->sum('amount_rupiah');

        return Money::fromInt($componentTotal + $legacyTotal);
    }

    public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
    {
        $normalizedNoteId = $this->normalize($noteId, 'Note id pada customer refund wajib ada.');

        $hasComponentRefunds = DB::table('refund_component_allocations')
            ->where('note_id', $normalizedNoteId)
            ->exists();

        if (! $hasComponentRefunds) {
            return Money::fromInt((int) DB::table('customer_refunds')
                ->where('note_id', $normalizedNoteId)
                ->sum('amount_rupiah'));
        }

        $currentComponentTotal = (int) DB::table('refund_component_allocations as refunds')
            ->where('refunds.note_id', $normalizedNoteId)
            ->whereExists(static function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('payment_component_allocations as payments')
                    ->whereColumn('payments.note_id', 'refunds.note_id')
                    ->whereColumn('payments.customer_payment_id', 'refunds.customer_payment_id')
                    ->whereColumn('payments.work_item_id', 'refunds.work_item_id')
                    ->whereColumn('payments.component_type', 'refunds.component_type')
                    ->whereColumn('payments.component_ref_id', 'refunds.component_ref_id');
            })
            ->sum('refunds.refunded_amount_rupiah');

        return Money::fromInt($currentComponentTotal);
    }

    public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
    {
        $paymentId = $this->normalize($customerPaymentId, 'Customer payment id pada customer refund wajib ada.');
        $normalizedNoteId = $this->normalize($noteId, 'Note id pada customer refund wajib ada.');
        $componentTotal = (int) DB::table('refund_component_allocations')
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', $normalizedNoteId)
            ->sum('refunded_amount_rupiah');

        $componentRefundIds = DB::table('refund_component_allocations')
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', $normalizedNoteId)
            ->select('customer_refund_id');

        $legacyTotal = (int) DB::table('customer_refunds')
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', $normalizedNoteId)
            ->whereNotIn('id', $componentRefundIds)
            ->sum('amount_rupiah');

        return Money::fromInt($componentTotal + $legacyTotal);
    }

    private function normalize(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new DomainException($message);
        }

        return $normalized;
    }
}
