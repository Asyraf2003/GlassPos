<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

trait NoteOperationalStateMutations
{
    public function close(string $actorId, DateTimeImmutable $occurredAt): void
    {
        $actor = trim($actorId);

        if ($actor === '') {
            throw new DomainException('Actor close note wajib ada.');
        }

        if ($this->noteState !== Note::STATE_OPEN) {
            throw new DomainException('Hanya note open yang boleh ditutup.');
        }

        $this->noteState = Note::STATE_CLOSED;
        $this->closedAt = $occurredAt;
        $this->closedByActorId = $actor;
    }

    public function refund(string $actorId, DateTimeImmutable $occurredAt): void
    {
        $actor = trim($actorId);

        if ($actor === '') {
            throw new DomainException('Actor refund note wajib ada.');
        }

        if ($this->noteState === Note::STATE_REFUNDED) {
            return;
        }

        if ($this->noteState !== Note::STATE_CLOSED) {
            throw new DomainException('Hanya note closed yang boleh di-refund.');
        }

        $this->noteState = Note::STATE_REFUNDED;

        if ($this->closedAt === null) {
            $this->closedAt = $occurredAt;
        }

        if ($this->closedByActorId === null) {
            $this->closedByActorId = $actor;
        }
    }

    public function reopen(string $actorId, DateTimeImmutable $occurredAt): void
    {
        $actor = trim($actorId);

        if ($actor === '') {
            throw new DomainException('Actor reopen note wajib ada.');
        }

        if ($this->noteState !== Note::STATE_CLOSED) {
            throw new DomainException('Hanya note closed yang boleh dibuka kembali.');
        }

        $this->noteState = Note::STATE_OPEN;
        $this->reopenedAt = $occurredAt;
        $this->reopenedByActorId = $actor;
    }

    public function reopenForRevisionOutstanding(string $actorId, DateTimeImmutable $occurredAt): void
    {
        if ($this->noteState !== Note::STATE_REFUNDED) {
            $this->reopen($actorId, $occurredAt);
            return;
        }

        $actor = trim($actorId);
        if ($actor === '' || $this->totalRupiah()->amount() <= 0) {
            throw new DomainException('Revisi note refund membutuhkan actor dan tagihan aktif baru.');
        }

        $this->noteState = Note::STATE_OPEN;
        $this->reopenedAt = $occurredAt;
        $this->reopenedByActorId = $actor;
    }
}
