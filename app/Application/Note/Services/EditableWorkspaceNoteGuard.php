<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteReaderPort;

final class EditableWorkspaceNoteGuard
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteOperationalStatusResolver $statuses,
    ) {}

    public function assertWorkspaceEditPageAccessible(string $noteId): void
    {
        $normalized = trim($noteId);

        if ($normalized === '') {
            throw new DomainException('Note id wajib ada.');
        }

        $note = $this->notes->getById($normalized);

        if ($note === null) {
            throw new DomainException('Nota tidak ditemukan.');
        }
    }

    public function assertEditable(string $noteId): void
    {
        $normalized = trim($noteId);

        if ($normalized === '') {
            throw new DomainException('Note id wajib ada.');
        }

        $note = $this->notes->getById($normalized);

        if ($note === null) {
            throw new DomainException('Nota tidak ditemukan.');
        }

        $note->assertNotCancelled();

        if ($this->statuses->isClose($note)) {
            throw new DomainException('Nota close tidak boleh diedit lewat workspace.');
        }
    }
}
