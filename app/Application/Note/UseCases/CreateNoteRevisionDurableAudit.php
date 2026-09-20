<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Core\Note\Revision\NoteRevision;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\UuidPort;

final class CreateNoteRevisionDurableAudit
{
    public function __construct(
        private readonly AuditEventWriterPort $events,
        private readonly ActorAccessReaderPort $actors,
        private readonly UuidPort $uuid,
        private readonly CreateNoteRevisionAuditPayloadBuilder $payloads,
    ) {
    }

    public function record(string $noteRootId, string $parentRevisionId, ?string $actorId, string $reason, NoteRevision $revision): void
    {
        $actorId = $actorId !== null && trim($actorId) !== '' ? trim($actorId) : null;
        $actor = $actorId !== null ? $this->actors->findByActorId($actorId) : null;
        $this->events->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'note',
            aggregateType: 'note_revision',
            aggregateId: $revision->id(),
            eventName: 'note_revision_created',
            actorId: $actorId,
            actorRole: $actor?->role()->value(),
            reason: $reason,
            sourceChannel: null,
            requestId: null,
            correlationId: null,
            occurredAt: $revision->createdAt(),
            metadata: $this->payloads->build($noteRootId, $parentRevisionId, $actorId, $reason, $revision),
        ));
    }
}
