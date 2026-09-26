<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Core\Note\Note\Note;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class NoteRestoreAuditRecorder
{
    public function __construct(
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly AuditEventWriterPort $events,
        private readonly UuidPort $uuid,
    ) {}

    public function record(Note $after, array $command, string $role, DateTimeImmutable $at, array $before, array $metadata): string
    {
        $eventId = $this->timeline->record(
            $after->id(), 'note_restored', $command['_actor_id'], $role,
            $command['reason'], $at, $before, $this->snapshots->build($after), metadata: $metadata,
        );
        $this->events->write(new AuditEventWrite(
            $this->uuid->generate(), 'note', 'note', $after->id(), 'note_restored',
            $command['_actor_id'], $role, $command['reason'], 'application', null, null,
            $at, [...$metadata, 'restore_event_id' => $eventId],
        ));

        return $eventId;
    }
}
