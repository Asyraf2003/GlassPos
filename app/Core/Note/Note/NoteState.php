<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

trait NoteState
{
    /** @param list<WorkItem> $workItems */
    private function __construct(
        private string $id,
        private string $customerName,
        private ?string $customerPhone,
        private DateTimeImmutable $transactionDate,
        private DateTimeImmutable $dueDate,
        private ?string $operationalNote,
        private array $workItems,
        private Money $totalRupiah,
        private string $noteState,
        private ?DateTimeImmutable $closedAt,
        private ?string $closedByActorId,
        private ?DateTimeImmutable $reopenedAt,
        private ?string $reopenedByActorId,
    ) {
    }

    public function id(): string { return $this->id; }
    public function customerName(): string { return $this->customerName; }
    public function customerPhone(): ?string { return $this->customerPhone; }
    public function transactionDate(): DateTimeImmutable { return $this->transactionDate; }
    public function dueDate(): DateTimeImmutable { return $this->dueDate; }
    public function operationalNote(): ?string { return $this->operationalNote; }

    /** @return list<WorkItem> */
    public function workItems(): array { return $this->workItems; }

    public function totalRupiah(): Money { return $this->totalRupiah; }
    public function noteState(): string { return $this->noteState; }
    public function closedAt(): ?DateTimeImmutable { return $this->closedAt; }
    public function closedByActorId(): ?string { return $this->closedByActorId; }
    public function reopenedAt(): ?DateTimeImmutable { return $this->reopenedAt; }
    public function reopenedByActorId(): ?string { return $this->reopenedByActorId; }
    public function isOpen(): bool { return $this->noteState === Note::STATE_OPEN; }
    public function isClosed(): bool { return $this->noteState === Note::STATE_CLOSED; }
    public function isRefunded(): bool { return $this->noteState === Note::STATE_REFUNDED; }
}
