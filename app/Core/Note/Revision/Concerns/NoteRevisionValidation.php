<?php

declare(strict_types=1);

namespace App\Core\Note\Revision\Concerns;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Shared\Exceptions\DomainException;

trait NoteRevisionValidation
{
    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     */
    private static function assertValidState(
        string $id,
        string $noteRootId,
        int $revisionNumber,
        string $customerName,
        int $grandTotalRupiah,
        array $lines,
    ): void {
        if ($id === '') {
            throw new DomainException('Note revision id wajib diisi.');
        }

        if ($noteRootId === '') {
            throw new DomainException('Note root id wajib diisi.');
        }

        if ($revisionNumber <= 0) {
            throw new DomainException('Revision number wajib lebih dari nol.');
        }

        if ($customerName === '') {
            throw new DomainException('Customer name revision wajib diisi.');
        }

        if ($grandTotalRupiah < 0) {
            throw new DomainException('Grand total revision tidak boleh negatif.');
        }

        foreach ($lines as $line) {
            if (! $line instanceof NoteRevisionLineSnapshot) {
                throw new DomainException('Semua line revision wajib berupa NoteRevisionLineSnapshot.');
            }

            if ($line->noteRevisionId() !== $id) {
                throw new DomainException('Semua line revision wajib belong ke note revision yang sama.');
            }
        }
    }
}
