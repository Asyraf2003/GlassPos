<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\Concerns\BuildsNoteRevisionLines;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;
use DateTimeImmutable;

final class NoteRevisionBootstrapFactory
{
    use BuildsNoteRevisionLines;

    public function __construct(
        private readonly NoteRevisionLinePayloadMapper $linePayloads,
    ) {
    }

    public function createInitialRevision(
        string $revisionId,
        Note $note,
        ?string $actorId,
        DateTimeImmutable $createdAt,
        ?string $reason = 'Bootstrap initial revision from current root note state',
    ): NoteRevision {
        [$lines, $grandTotal] = $this->buildLinesAndGrandTotal(trim($revisionId), $note->workItems());

        return NoteRevision::create(
            trim($revisionId),
            $note->id(),
            1,
            null,
            $actorId,
            $reason,
            $note->customerName(),
            $note->customerPhone(),
            $note->transactionDate(),
            $grandTotal,
            $lines,
            $createdAt,
        );
    }

    public function createNextRevision(
        string $revisionId,
        string $parentRevisionId,
        int $revisionNumber,
        Note $note,
        ?string $actorId,
        DateTimeImmutable $createdAt,
        ?string $reason,
    ): NoteRevision {
        [$lines, $grandTotal] = $this->buildLinesAndGrandTotal(trim($revisionId), $note->workItems());

        return NoteRevision::create(
            trim($revisionId),
            $note->id(),
            $revisionNumber,
            trim($parentRevisionId),
            $actorId,
            $reason,
            $note->customerName(),
            $note->customerPhone(),
            $note->transactionDate(),
            $grandTotal,
            $lines,
            $createdAt,
        );
    }
}
