<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Policies\CashierNoteAccessGuard;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final readonly class CashierNoteRouteAccessData
{
    public function __construct(
        private NoteReaderPort $notes,
        private CashierNoteAccessGuard $guard,
        private ClockPort $clock,
        private NoteOutstandingPaymentAmountResolver $outstanding,
    ) {}

    public function ensureCanViewFinanceQueue(string $noteId): bool
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

    public function ensureCanCollectOutstanding(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return false;
        }

        $this->guard->assertCanCollectOutstanding(
            $note,
            $this->clock->now(),
            $this->outstanding->resolveFull($noteId)->isSuccess(),
        );

        return true;
    }

    public function ensureCanView(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return false;
        }

        $this->guard->assertCanView($note, $this->clock->now());

        return true;
    }

    public function ensureCanOpenWorkspaceEdit(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return false;
        }

        $this->guard->assertCanView($note, $this->clock->now());

        if ($note->isRefunded()) {
            throw new DomainException('Kasir tidak boleh membuka workspace edit untuk note refund.');
        }

        return true;
    }

    public function ensureCanMutateOpenNote(string $noteId): bool
    {
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return false;
        }

        $this->guard->assertCanMutateOpenNote($note, $this->clock->now());

        return true;
    }
}
