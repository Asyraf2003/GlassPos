<?php

declare(strict_types=1);

namespace App\Application\IdentityAccess\Services;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;

final class CreateUserAccessAuditRecorder
{
    public function __construct(
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {}

    /** @param array<string, mixed> $snapshot */
    public function record(
        array $snapshot,
        ?string $performedByActorId,
        ?string $sourceChannel,
    ): void {
        $this->audit->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'identity_access',
            aggregateType: 'user_access',
            aggregateId: (string) $snapshot['id'],
            eventName: 'user_access_created',
            actorId: $this->nullable($performedByActorId),
            actorRole: null,
            reason: null,
            sourceChannel: $this->nullable($sourceChannel),
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: ['role' => $snapshot['role']],
            snapshots: [new AuditEventSnapshotWrite('after', $snapshot)],
        ));
    }

    private function nullable(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
