<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\AllocatePaymentAcrossComponents;
use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Core\Note\Note\Note;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationWriterPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class NoteReplacementPaymentAllocationReconciler
{
    public function __construct(
        private readonly PaymentComponentAllocationReaderPort $reader,
        private readonly PaymentComponentAllocationWriterPort $writer,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly ResolveNotePayableComponents $components,
        private readonly AllocatePaymentAcrossComponents $allocator,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function captureAllocatedAmounts(string $noteId): array
    {
        $amounts = [];

        foreach ($this->reader->listByNoteId($noteId) as $allocation) {
            $paymentId = $allocation->customerPaymentId();
            $amounts[$paymentId] = ($amounts[$paymentId] ?? 0)
                + $allocation->allocatedAmountRupiah()->amount();
        }

        foreach ($this->refunds->listByNoteId($noteId) as $refund) {
            $paymentId = $refund->customerPaymentId();
            $amounts[$paymentId] = max(($amounts[$paymentId] ?? 0) - $refund->refundedAmountRupiah()->amount(), 0);
        }

        return array_filter($amounts, static fn (int $amount): bool => $amount > 0);
    }

    public function deleteExisting(string $noteId): void
    {
        $this->writer->deleteByNoteId($noteId);
    }

    /**
     * @param array<string, int> $paymentAmounts
     */
    public function rebuild(Note $note, array $paymentAmounts): void
    {
        $components = $this->components->fromNote($note);

        foreach ($paymentAmounts as $paymentId => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $allocations = $this->allocator->allocate(
                $paymentId,
                $note->id(),
                Money::fromInt($amount),
                $components,
            );

            $this->writer->createMany($allocations);
        }
    }
}
