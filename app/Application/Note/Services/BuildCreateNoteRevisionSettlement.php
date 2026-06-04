<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use DateTimeImmutable;

final class BuildCreateNoteRevisionSettlement
{
    public function __construct(
        private readonly PaymentComponentAllocationReaderPort $componentPayments,
        private readonly PaymentAllocationReaderPort $legacyPayments,
        private readonly RefundComponentAllocationReaderPort $componentRefunds,
        private readonly CustomerRefundReaderPort $legacyRefunds,
        private readonly NoteRevisionSurplusRefundPaymentReaderPort $surplusRefundPayments,
        private readonly NoteRevisionSurplusDispositionReaderPort $surplusDispositions,
        private readonly BuildNoteRevisionSettlement $builder,
    ) {
    }

    public function build(
        string $id,
        string $noteRevisionId,
        string $noteRootId,
        int $grossTotalRupiah,
        DateTimeImmutable $createdAt,
    ): NoteRevisionSettlement {
        $componentPaid = $this->componentPayments
            ->getTotalAllocatedAmountByNoteId($noteRootId)
            ->amount();

        $componentRefunded = $this->componentRefunds
            ->getTotalRefundedAmountByNoteId($noteRootId)
            ->amount();

        $carryForwardPaid = max(
            $this->legacyPayments->getTotalAllocatedAmountByNoteId($noteRootId)->amount(),
            $componentPaid,
        );

        $ordinaryRefunded = max(
            $this->legacyRefunds->getTotalRefundedAmountByNoteId($noteRootId)->amount(),
            $componentRefunded,
        );

        $surplusRefundPaid = $this->surplusRefundPayments
            ->sumActiveAmountByNoteRootId($noteRootId);

        $surplusRefundDue = $this->surplusDispositions
            ->sumActiveRefundDueAmountByNoteRootId($noteRootId);

        $surplusObligationEffect = max($surplusRefundPaid, $surplusRefundDue);

        $carryForwardRefunded = $ordinaryRefunded + $surplusObligationEffect;

        return $this->builder->build(
            $id,
            $noteRevisionId,
            $noteRootId,
            $grossTotalRupiah,
            $carryForwardPaid,
            $carryForwardRefunded,
            $createdAt,
        );
    }
}
