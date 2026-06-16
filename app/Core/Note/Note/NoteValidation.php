<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

trait NoteValidation
{
    private static function assertValidIdentity(string $id, string $name): void
    {
        if (trim($id) === '') {
            throw new DomainException('Note id wajib ada.');
        }

        if (trim($name) === '') {
            throw new DomainException('Customer name wajib ada.');
        }
    }

    /** @param list<WorkItem> $items */
    private static function assertValidWorkItems(array $items): void
    {
        foreach ($items as $item) {
            if (!$item instanceof WorkItem) {
                throw new DomainException('Work item tidak valid.');
            }
        }
    }

    /** @param list<WorkItem> $items */
    private static function calculateTotalFromWorkItems(array $items): Money
    {
        $total = Money::zero();

        foreach ($items as $item) {
            if ($item->status() === WorkItem::STATUS_CANCELED) {
                continue;
            }

            $total = $total->add($item->subtotalRupiah());
        }

        return $total;
    }

    private static function assertValidNoteTaxBreakdown(
        Money $subtotalBeforeNoteTaxRupiah,
        ?string $noteTaxInput,
        string $noteTaxMode,
        ?int $noteTaxRateBasisPoints,
        Money $noteTaxAmountRupiah,
    ): void {
        $subtotalBeforeNoteTaxRupiah->ensureNotNegative('Subtotal sebelum pajak note tidak boleh negatif.');
        $noteTaxAmountRupiah->ensureNotNegative('Pajak note tidak boleh negatif.');

        if (! in_array($noteTaxMode, [Note::TAX_MODE_NONE, Note::TAX_MODE_PERCENT, Note::TAX_MODE_FIXED], true)) {
            throw new DomainException('Tax mode note tidak valid.');
        }

        if ($noteTaxMode === Note::TAX_MODE_NONE) {
            if ($noteTaxInput !== null && trim($noteTaxInput) !== '') {
                throw new DomainException('Tax input note harus kosong ketika tax mode none.');
            }

            if ($noteTaxRateBasisPoints !== null) {
                throw new DomainException('Tax rate note harus kosong ketika tax mode none.');
            }

            if ($noteTaxAmountRupiah->amount() !== 0) {
                throw new DomainException('Tax amount note harus nol ketika tax mode none.');
            }

            return;
        }

        if ($noteTaxMode === Note::TAX_MODE_PERCENT && ($noteTaxRateBasisPoints === null || $noteTaxRateBasisPoints < 0)) {
            throw new DomainException('Tax rate basis points note tidak valid.');
        }

        if ($noteTaxMode !== Note::TAX_MODE_PERCENT && $noteTaxRateBasisPoints !== null) {
            throw new DomainException('Tax rate basis points note hanya boleh ada untuk mode percent.');
        }
    }

    private static function normalizeTaxInput(?string $taxInput): ?string
    {
        if ($taxInput === null) {
            return null;
        }

        $taxInput = trim($taxInput);

        return $taxInput === '' ? null : $taxInput;
    }
}
