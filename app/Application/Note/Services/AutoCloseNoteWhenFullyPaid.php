<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class AutoCloseNoteWhenFullyPaid
{
    private const SYSTEM_ACTOR_ID = 'system';
    private const SYSTEM_ACTOR_ROLE = 'system';
    private const AUTO_CLOSE_REASON = 'AUTO_CLOSE_ON_FULL_PAYMENT';

    public function __construct(
        private readonly NoteWriterPort $notes,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly ClockPort $clock,
    ) {
    }

    public function closeIfEligible(Note $note, string $customerPaymentId): void
    {
        if ($note->isClosed()) {
            return;
        }

        $netPaid = $this->allocations->getTotalAllocatedAmountByNoteId($note->id())->amount()
            - $this->refunds->getTotalRefundedAmountByNoteId($note->id())->amount();

        if ($netPaid < $note->totalRupiah()->amount()) {
            return;
        }

        $before = $this->snapshots->build($note);
        $occurredAt = $this->clock->now();

        $note->close(self::SYSTEM_ACTOR_ID, $occurredAt);
        $this->notes->updateOperationalState($note);

        $this->timeline->record(
            $note->id(),
            'note_closed',
            self::SYSTEM_ACTOR_ID,
            self::SYSTEM_ACTOR_ROLE,
            self::AUTO_CLOSE_REASON,
            $occurredAt,
            $before,
            $this->snapshots->build($note),
            $customerPaymentId,
            null,
            ['net_paid_rupiah' => $netPaid],
        );
    }
}
