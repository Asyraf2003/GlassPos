<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

interface NoteCorrectionHistoryReaderPort
{
    public function isUnrestoredCancellation(string $noteId, string $cancellationEventId): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function findLatestNoteCorrections(string $noteId, int $limit = 10): array;
}
