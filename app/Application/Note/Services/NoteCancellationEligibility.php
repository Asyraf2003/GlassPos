<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteRevisionSettlementReaderPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class NoteCancellationEligibility
{
    public function __construct(
        private readonly PaymentAllocationReaderPort $payments,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly NoteRevisionSettlementReaderPort $settlements,
        private readonly NoteCurrentRevisionResolver $revisions,
    ) {}

    public function assertEligible(Note $note): void
    {
        if ($note->isCancelled()) {
            throw new DomainException('NOTE_ALREADY_CANCELLED');
        }
        if ($this->payments->getTotalPaymentAmountByNoteId($note->id())->amount() > 0
            || $this->payments->getTotalAllocatedAmountByNoteId($note->id())->amount() > 0
            || $this->refunds->getTotalRefundedAmountByNoteId($note->id())->amount() > 0) {
            throw new DomainException('REFUND_REQUIRED');
        }
        $settlements = $this->settlements->listByNoteRootId($note->id());
        foreach ($settlements as $settlement) {
            if ($settlement->carryForwardPaidRupiah > 0 || $settlement->carryForwardRefundedRupiah > 0) {
                throw new DomainException('REFUND_REQUIRED');
            }
        }
        $current = $this->revisions->resolveOrFail($note->id());
        if (count($settlements) < $current->revisionNumber() - 1 || $note->closedAt() !== null || $note->isRefunded()) {
            throw new DomainException('CANCELLATION_HISTORY_UNRESOLVED');
        }
        foreach ($note->workItems() as $item) {
            if ($item->externalPurchaseLines() !== []) {
                throw new DomainException('EXTERNAL_REFUND_REQUIRED');
            }
        }
    }
}
