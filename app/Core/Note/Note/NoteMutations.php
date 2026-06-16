<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

trait NoteMutations
{
    public function updateHeader(
        string $customerName,
        ?string $customerPhone,
        DateTimeImmutable $transactionDate,
        ?string $operationalNote = null,
    ): void {
        self::assertValidIdentity($this->id, $customerName);

        $this->customerName = trim($customerName);
        $this->customerPhone = self::normalizeCustomerPhone($customerPhone);
        $this->transactionDate = $transactionDate;
        $this->dueDate = NoteDueDateCalculator::calculate($transactionDate);
        $this->operationalNote = self::normalizeOperationalNote($operationalNote);
    }

    /** @param list<WorkItem> $workItems */
    public function replaceWorkItems(array $workItems): void
    {
        self::assertValidWorkItems($workItems);
        $this->assertWorkItemsBelongToThisNote($workItems);
        $this->assertNoDuplicateWorkItems($workItems);

        $this->workItems = array_values($workItems);
        $this->resetNoteTaxToSubtotal(self::calculateTotalFromWorkItems($this->workItems));
    }

    public function addWorkItem(WorkItem $item): void
    {
        if ($item->noteId() !== $this->id) {
            throw new DomainException('Work item tidak belong ke note ini.');
        }

        foreach ($this->workItems as $existing) {
            if ($existing->id() === $item->id()) {
                throw new DomainException('Work item ID duplikat.');
            }

            if ($existing->lineNo() === $item->lineNo()) {
                throw new DomainException('Line number duplikat.');
            }
        }

        $this->workItems[] = $item;
        $this->resetNoteTaxToSubtotal(self::calculateTotalFromWorkItems($this->workItems));
    }

    public function syncTotalRupiah(Money $total): void
    {
        $total->ensureNotNegative('Total note tidak boleh negatif.');
        $this->resetNoteTaxToSubtotal($total);
    }

    public function syncNoteTax(
        Money $subtotalBeforeNoteTaxRupiah,
        ?string $noteTaxInput,
        string $noteTaxMode,
        ?int $noteTaxRateBasisPoints,
        Money $noteTaxAmountRupiah,
    ): void {
        self::assertValidNoteTaxBreakdown(
            $subtotalBeforeNoteTaxRupiah,
            self::normalizeTaxInput($noteTaxInput),
            $noteTaxMode,
            $noteTaxRateBasisPoints,
            $noteTaxAmountRupiah,
        );

        $this->subtotalBeforeNoteTaxRupiah = $subtotalBeforeNoteTaxRupiah;
        $this->noteTaxInput = self::normalizeTaxInput($noteTaxInput);
        $this->noteTaxMode = $noteTaxMode;
        $this->noteTaxRateBasisPoints = $noteTaxRateBasisPoints;
        $this->noteTaxAmountRupiah = $noteTaxAmountRupiah;
        $this->totalRupiah = $subtotalBeforeNoteTaxRupiah->add($noteTaxAmountRupiah);
    }

    private function resetNoteTaxToSubtotal(Money $subtotal): void
    {
        $subtotal->ensureNotNegative('Subtotal note tidak boleh negatif.');

        $this->subtotalBeforeNoteTaxRupiah = $subtotal;
        $this->noteTaxInput = null;
        $this->noteTaxMode = Note::TAX_MODE_NONE;
        $this->noteTaxRateBasisPoints = null;
        $this->noteTaxAmountRupiah = Money::zero();
        $this->totalRupiah = $subtotal;
    }

    /** @param list<WorkItem> $workItems */
    private function assertWorkItemsBelongToThisNote(array $workItems): void
    {
        foreach ($workItems as $item) {
            if ($item->noteId() !== $this->id) {
                throw new DomainException('Work item tidak belong ke note ini.');
            }
        }
    }

    /** @param list<WorkItem> $workItems */
    private function assertNoDuplicateWorkItems(array $workItems): void
    {
        $ids = [];
        $lineNos = [];

        foreach ($workItems as $item) {
            if (isset($ids[$item->id()])) {
                throw new DomainException('Work item ID duplikat.');
            }

            if (isset($lineNos[$item->lineNo()])) {
                throw new DomainException('Line number duplikat.');
            }

            $ids[$item->id()] = true;
            $lineNos[$item->lineNo()] = true;
        }
    }
}
