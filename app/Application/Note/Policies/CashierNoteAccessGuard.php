<?php

declare(strict_types=1);

namespace App\Application\Note\Policies;

use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class CashierNoteAccessGuard
{
    public function assertCanView(Note $note, DateTimeImmutable $today): void
    {
        if (! $this->isWithinCashierDateWindow($note, $today)) {
            throw new DomainException('Kasir hanya boleh mengakses note untuk hari ini dan kemarin.');
        }
    }

    public function assertCanViewFinanceQueue(Note $note, DateTimeImmutable $today, bool $hasOutstanding): void
    {
        if ($this->isWithinCashierDateWindow($note, $today)) {
            return;
        }

        $closedToday = $note->closedAt()?->format('Y-m-d') === $today->format('Y-m-d');

        if (($hasOutstanding && ! $note->isRefunded()) || $closedToday) {
            return;
        }

        throw new DomainException('Nota tidak termasuk antrean keuangan kasir yang aktif.');
    }

    public function assertCanCollectOutstanding(Note $note, DateTimeImmutable $today, bool $hasOutstanding): void
    {
        if ($this->isWithinCashierDateWindow($note, $today)) {
            return;
        }

        if ($hasOutstanding && ! $note->isRefunded()) {
            return;
        }

        throw new DomainException('Nota tidak termasuk antrean pembayaran kasir yang aktif.');
    }

    public function assertCanMutateOpenNote(Note $note, DateTimeImmutable $today): void
    {
        $this->assertCanView($note, $today);

        if ($note->isClosed() || $note->isRefunded()) {
            throw new DomainException('Kasir tidak boleh memproses note yang sudah ditutup atau refund lewat route ini.');
        }
    }

    public function assertCanAccess(Note $note, DateTimeImmutable $today): void
    {
        $this->assertCanMutateOpenNote($note, $today);
    }

    private function isWithinCashierDateWindow(Note $note, DateTimeImmutable $today): bool
    {
        $noteDate = $note->transactionDate()->format('Y-m-d');
        $todayDate = $today->format('Y-m-d');
        $yesterdayDate = $today->modify('-1 day')->format('Y-m-d');

        return in_array($noteDate, [$todayDate, $yesterdayDate], true);
    }
}
