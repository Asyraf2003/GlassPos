<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Core\Note\Note\Note;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class NoteCancellationAudit
{
    public function __construct(
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly AuditEventWriterPort $events,
        private readonly UuidPort $uuid,
    ) {}

    /** @param list<string> $movementIds */
    public function record(Note $beforeNote, array $before, array $payload, string $role, array $movementIds, DateTimeImmutable $at): string
    {
        $metadata = [
            'note_id' => $beforeNote->id(),
            'base_revision_id' => $payload['base_revision_id'],
            'inventory_movement_ids' => $movementIds,
            'affected_line_ids' => array_map(static fn ($item): string => $item->id(), $beforeNote->workItems()),
        ];
        $eventId = $this->timeline->record(
            $beforeNote->id(), 'note_cancelled', $payload['_actor_id'], $role,
            $payload['reason'], $at, $before,
            $this->snapshots->build($beforeNote), metadata: $metadata,
        );
        $this->events->write(new AuditEventWrite(
            $this->uuid->generate(), 'note', 'note', $beforeNote->id(), 'note_cancelled',
            $payload['_actor_id'], $role, $payload['reason'], 'application', null, null,
            $at, [...$metadata, 'cancellation_id' => $eventId],
        ));

        return $eventId;
    }
}
