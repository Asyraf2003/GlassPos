<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Ports\Out\Note\NoteWriterPort;
use DateTimeImmutable;

final class ReopenNoteForRevisionOutstanding
{
    public function __construct(
        private readonly BuildCreateNoteRevisionSettlement $settlements,
        private readonly NoteWriterPort $notes,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PersistNoteMutationTimeline $timeline,
    ) {}

    public function reopenIfNeeded(
        Note $root,
        string $revisionId,
        ?string $actorId,
        string $reason,
        DateTimeImmutable $occurredAt,
    ): void {
        if (! $root->isClosed() && ! $root->isRefunded()) {
            return;
        }

        $settlement = $this->settlements->build(
            $revisionId.'-settlement',
            $revisionId,
            $root->id(),
            $root->totalRupiah()->amount(),
            $occurredAt,
        );

        if ($settlement->outstandingRupiah === 0) {
            return;
        }

        $actor = trim((string) $actorId);
        $before = $this->snapshots->build($root);
        $root->reopenForRevisionOutstanding($actor !== '' ? $actor : 'system', $occurredAt);
        $this->notes->updateOperationalState($root);
        $this->timeline->record(
            $root->id(),
            'note_reopened',
            $root->reopenedByActorId() ?? 'system',
            $actor !== '' ? 'admin' : 'system',
            trim($reason) !== '' ? $reason : 'REOPEN_ON_REVISION_OUTSTANDING',
            $occurredAt,
            $before,
            $this->snapshots->build($root),
            null,
            null,
            ['revision_id' => $revisionId, 'outstanding_rupiah' => $settlement->outstandingRupiah],
        );
    }
}
