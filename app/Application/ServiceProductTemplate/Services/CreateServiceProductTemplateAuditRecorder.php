<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;

final class CreateServiceProductTemplateAuditRecorder
{
    public function __construct(
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {}

    /** @param array<string, mixed> $snapshot */
    public function record(
        string $templateId,
        array $snapshot,
        ?string $actorId,
        ?string $sourceChannel,
    ): void {
        $this->audit->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'service_product_template',
            aggregateType: 'service_product_template',
            aggregateId: $templateId,
            eventName: 'service_product_template_created',
            actorId: $this->nullable($actorId),
            actorRole: null,
            reason: null,
            sourceChannel: $this->nullable($sourceChannel),
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: ['service_product_template_id' => $templateId],
            snapshots: [new AuditEventSnapshotWrite('after', $snapshot)],
        ));
    }

    private function nullable(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
