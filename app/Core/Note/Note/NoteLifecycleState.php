<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use DateTimeImmutable;

trait NoteLifecycleState
{
    public function noteState(): string
    {
        return $this->noteState;
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function closedByActorId(): ?string
    {
        return $this->closedByActorId;
    }

    public function reopenedAt(): ?DateTimeImmutable
    {
        return $this->reopenedAt;
    }

    public function reopenedByActorId(): ?string
    {
        return $this->reopenedByActorId;
    }

    public function isOpen(): bool
    {
        return $this->noteState === Note::STATE_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->noteState === Note::STATE_CLOSED;
    }

    public function isRefunded(): bool
    {
        return $this->noteState === Note::STATE_REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this->noteState === Note::STATE_CANCELLED;
    }
}
