<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\DTO\PayableNoteComponent;
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
        $refundedByComponent = [];

        foreach ($this->refunds->listByNoteId($noteId) as $refund) {
            $key = $refund->customerPaymentId().'::'.$refund->componentType().'::'.$refund->componentRefId();
            $refundedByComponent[$key] = ($refundedByComponent[$key] ?? 0)
                + $refund->refundedAmountRupiah()->amount();
        }

        foreach ($this->reader->listByNoteId($noteId) as $allocation) {
            $paymentId = $allocation->customerPaymentId();
            $key = $paymentId.'::'.$allocation->componentType().'::'.$allocation->componentRefId();
            // Only the original allocated component still contains its refund.
            // Replacement components already carry net settlement under fresh identities.
            $amounts[$paymentId] = ($amounts[$paymentId] ?? 0)
                + max($allocation->allocatedAmountRupiah()->amount() - ($refundedByComponent[$key] ?? 0), 0);
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
        $remainingReplayableAmount = $this->totalComponentAmount($components);

        foreach ($paymentAmounts as $paymentId => $amount) {
            if ($amount <= 0 || $remainingReplayableAmount <= 0) {
                continue;
            }

            $replayAmount = min($amount, $remainingReplayableAmount);

            if ($replayAmount <= 0) {
                continue;
            }

            $allocations = $this->allocator->allocate(
                $paymentId,
                $note->id(),
                Money::fromInt($replayAmount),
                $components,
            );

            $this->writer->createMany($allocations);
            $remainingReplayableAmount -= $replayAmount;
        }
    }

    /**
     * @param list<PayableNoteComponent> $components
     */
    private function totalComponentAmount(array $components): int
    {
        return array_reduce(
            $components,
            static fn (int $total, mixed $component): int => $total + $component->amountRupiah()->amount(),
            0,
        );
    }
}
