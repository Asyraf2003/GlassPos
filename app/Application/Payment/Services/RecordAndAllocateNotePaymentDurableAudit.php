<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Payment\DTO\RecordedNotePayment;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\UuidPort;

final class RecordAndAllocateNotePaymentDurableAudit
{
    public function __construct(
        private readonly AuditEventWriterPort $events,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly ActorAccessReaderPort $actors,
    ) {
    }

    /** @param list<string> $selectedRowIds */
    public function record(RecordedNotePayment $recorded, string $noteId, array $selectedRowIds, ?string $actorId): void
    {
        $payment = $recorded->payment();
        $cash = $recorded->cashDetail();
        $actorId = $actorId !== null && trim($actorId) !== '' ? trim($actorId) : null;
        $actor = $actorId !== null ? $this->actors->findByActorId($actorId) : null;

        $this->events->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'payment',
            aggregateType: 'customer_payment',
            aggregateId: $payment->id(),
            eventName: 'payment_allocated',
            actorId: $actorId,
            actorRole: $actor?->role()->value(),
            reason: null,
            sourceChannel: null,
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: [
                'payment_id' => $payment->id(),
                'note_id' => trim($noteId),
                'amount_rupiah' => $payment->amountRupiah()->amount(),
                'payment_method' => $payment->paymentMethod(),
                'amount_received_rupiah' => $cash?->amountReceivedRupiah()->amount(),
                'change_rupiah' => $cash?->changeRupiah()->amount(),
                'allocation_count' => $recorded->allocationCount(),
                'selected_row_ids' => $selectedRowIds,
            ],
        ));
    }
}
