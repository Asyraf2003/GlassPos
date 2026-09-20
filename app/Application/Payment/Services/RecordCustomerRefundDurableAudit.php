<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Payment\DTO\RecordedCustomerRefund;
use App\Application\Payment\UseCases\RecordCustomerRefundSupportTrait;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\UuidPort;

final class RecordCustomerRefundDurableAudit
{
    use RecordCustomerRefundSupportTrait;

    public function __construct(
        private readonly AuditEventWriterPort $events,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly ActorAccessReaderPort $actors,
    ) {}

    /** @param list<string> $selectedRowIds */
    public function record(RecordedCustomerRefund $recorded, string $actorId, array $selectedRowIds): void
    {
        $refund = $recorded->refund();
        $actor = $this->actors->findByActorId($actorId);
        $this->events->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'payment',
            aggregateType: 'customer_refund',
            aggregateId: $refund->id(),
            eventName: 'customer_refund_recorded',
            actorId: $actorId,
            actorRole: $actor?->role()->value(),
            reason: $refund->reason(),
            sourceChannel: null,
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: array_merge($this->formatAuditPayload($refund, $actorId), [
                'refund_allocation_count' => $recorded->allocationCount(),
                'selected_row_ids' => $selectedRowIds,
            ]),
        ));
    }
}
