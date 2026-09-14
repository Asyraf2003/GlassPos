<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Revision\NoteRevision;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteRevisionReaderPort;

final class NoteCurrentRevisionResolver
{
    public function __construct(
        private readonly NoteRevisionReaderPort $revisions,
    ) {
    }

    public function resolveOrFail(string $noteRootId): NoteRevision
    {
        $normalized = trim($noteRootId);

        if ($normalized === '') {
            throw new DomainException('Note root id wajib diisi untuk resolve current revision.');
        }

        $revision = $this->revisions->findCurrentByRootId($normalized);

        if ($revision === null) {
            throw new DomainException('Current revision untuk note root tidak ditemukan.');
        }

        return $revision;
    }

    /**
     * @return list<NoteRevision>
     */
    public function timeline(string $noteRootId, int $limit = 50): array
    {
        $normalized = trim($noteRootId);

        if ($normalized === '') {
            throw new DomainException('Note root id wajib diisi untuk membaca timeline revision.');
        }

        return $this->revisions->findTimelineByRootId($normalized, $limit);
    }

    public function nextRevisionNumber(string $noteRootId): int
    {
        $normalized = trim($noteRootId);

        if ($normalized === '') {
            throw new DomainException('Note root id wajib diisi untuk membaca next revision number.');
        }

        return $this->revisions->nextRevisionNumber($normalized);
    }

    public function hasCurrentRevision(string $noteRootId): bool
    {
        $normalized = trim($noteRootId);

        return $normalized !== ''
            && $this->revisions->findCurrentByRootId($normalized) !== null;
    }

    public function hasRevision(string $noteRootId): bool
    {
        $normalized = trim($noteRootId);

        if ($normalized === '') {
            return false;
        }

        return $this->revisions->existsForRootId($normalized);
    }
}
