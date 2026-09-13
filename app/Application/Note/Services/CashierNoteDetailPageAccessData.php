<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final readonly class CashierNoteDetailPageAccessData
{
    public function __construct(
        private NoteReaderPort $notes,
        private ClockPort $clock,
        private CashierNoteAccessGuard $guard,
        private NoteOutstandingPaymentAmountResolver $outstanding,
        private EditableWorkspaceNoteGuard $editable,
    ) {}

    public function canEditWorkspace(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);
        if ($note === null) {
            return false;
        }

        try {
            $this->guard->assertCanMutateOpenNote($note, $this->clock->now());
            $this->editable->assertEditable($noteId);
        } catch (DomainException) {
            return false;
        }

        return true;
    }

    public function ensureCanView(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return false;
        }

        $this->guard->assertCanViewFinanceQueue(
            $note,
            $this->clock->now(),
            $this->outstanding->resolveFull($noteId)->isSuccess(),
        );

        return true;
    }
}
