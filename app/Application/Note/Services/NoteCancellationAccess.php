<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\IdentityAccess\Policies\TransactionEntryPolicy;
use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final class NoteCancellationAccess
{
    public function __construct(
        private readonly TransactionEntryPolicy $entry,
        private readonly NoteReaderPort $notes,
        private readonly CashierNoteAccessGuard $cashierAccess,
        private readonly ClockPort $clock,
    ) {}

    public function authorize(string $noteId, string $actorId): string
    {
        $decision = $this->entry->decide($actorId, ['action' => 'cancel_note', 'note_id' => $noteId]);
        if ($decision->isFailure()) {
            throw new DomainException('CANCELLATION_FORBIDDEN');
        }
        $role = (string) ($decision->data()['role'] ?? '');
        $note = $this->notes->getById($noteId);
        if ($note === null) {
            throw new DomainException('NOTE_NOT_FOUND');
        }
        if ($role === 'kasir') {
            try {
                $this->cashierAccess->assertCanView($note, $this->clock->now());
            } catch (DomainException) {
                throw new DomainException('CANCELLATION_DATE_FORBIDDEN');
            }
        }

        return $role;
    }

    public function assertDateWindow(string $noteId, string $role): void
    {
        if ($role !== 'kasir') {
            return;
        }
        $note = $this->notes->getById($noteId);
        if ($note === null) {
            throw new DomainException('NOTE_NOT_FOUND');
        }
        try {
            $this->cashierAccess->assertCanView($note, $this->clock->now());
        } catch (DomainException) {
            throw new DomainException('CANCELLATION_DATE_FORBIDDEN');
        }
    }
}
