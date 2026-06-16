<?php

declare(strict_types=1);

namespace App\Core\Note\Revision\Concerns;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use DateTimeImmutable;

trait NoteRevisionAccessors
{
    public function id(): string
    {
        return $this->id;
    }

    public function noteRootId(): string
    {
        return $this->noteRootId;
    }

    public function revisionNumber(): int
    {
        return $this->revisionNumber;
    }

    public function parentRevisionId(): ?string
    {
        return $this->parentRevisionId;
    }

    public function createdByActorId(): ?string
    {
        return $this->createdByActorId;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function customerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function transactionDate(): DateTimeImmutable
    {
        return $this->transactionDate;
    }

    public function grandTotalRupiah(): int
    {
        return $this->grandTotalRupiah;
    }

    /**
     * @return list<NoteRevisionLineSnapshot>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }
}
