<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class CreateTransactionWorkspaceInlinePaymentAmountResolver
{
    public function __construct(
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
    ) {}

    /**
     * @param  array<string, mixed>  $payment
     */
    public function resolve(Note $note, array $payment): int
    {
        $decision = (string) ($payment['decision'] ?? 'skip');
        $outstandingAmount = $this->outstandingAmount($note);

        return match ($decision) {
            'pay_full' => $this->resolveFull($outstandingAmount),
            'pay_partial' => $this->resolvePartial($payment, $outstandingAmount),
            default => throw new DomainException('Keputusan pembayaran workspace tidak valid.'),
        };
    }

    private function resolveFull(int $outstandingAmount): int
    {
        if ($outstandingAmount <= 0) {
            throw new DomainException('Nota sudah tidak memiliki sisa tagihan.');
        }

        return $outstandingAmount;
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function resolvePartial(array $payment, int $outstandingAmount): int
    {
        $method = (string) ($payment['payment_method'] ?? '');
        $amount = $method === 'cash'
            ? (int) ($payment['amount_received_rupiah'] ?? 0)
            : (int) ($payment['amount_paid_rupiah'] ?? 0);

        if ($amount <= 0) {
            throw new DomainException('Nominal pembayaran sebagian wajib lebih dari 0.');
        }

        if ($outstandingAmount <= 0) {
            throw new DomainException('Nota sudah tidak memiliki sisa tagihan.');
        }

        if ($method !== 'cash' && $amount > $outstandingAmount) {
            throw new DomainException('Nominal pembayaran melebihi sisa tagihan.');
        }

        return min($amount, $outstandingAmount);
    }

    private function outstandingAmount(Note $note): int
    {
        $allocated = $this->allocations
            ->getTotalAllocatedAmountByNoteId($note->id())
            ->amount();

        $grossPaid = $this->allocations
            ->getTotalPaymentAmountByNoteId($note->id())
            ->amount();

        $refunded = $this->refunds
            ->getTotalRefundedAmountByNoteId($note->id())
            ->amount();

        $netPaid = max(max($allocated, $grossPaid) - $refunded, 0);

        return max($note->totalRupiah()->amount() - $netPaid, 0);
    }
}
