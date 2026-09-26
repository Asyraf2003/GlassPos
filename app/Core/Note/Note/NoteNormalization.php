<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Shared\Exceptions\DomainException;

trait NoteNormalization
{
    private static function assertValidOperationalState(string $noteState): void
    {
        if (! in_array(trim($noteState), [Note::STATE_OPEN, Note::STATE_CLOSED, Note::STATE_REFUNDED, Note::STATE_CANCELLED], true)) {
            throw new DomainException('State operasional note tidak valid.');
        }
    }

    private static function normalizeCustomerPhone(?string $customerPhone): ?string
    {
        if ($customerPhone === null) {
            return null;
        }

        $normalized = trim($customerPhone);

        return $normalized === '' ? null : $normalized;
    }

    private static function normalizeActorId(?string $actorId): ?string
    {
        if ($actorId === null) {
            return null;
        }

        $normalized = trim($actorId);

        return $normalized === '' ? null : $normalized;
    }

    private static function normalizeOperationalNote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
