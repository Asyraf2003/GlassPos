<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use InvalidArgumentException;

final class NoteTaxApplier
{
    public function __construct(
        private readonly TaxInputCalculator $taxInputCalculator,
    ) {
    }

    public function apply(Note $note, mixed $taxInput): void
    {
        $subtotal = Money::zero();

        foreach ($note->workItems() as $item) {
            if ($item->status() === WorkItem::STATUS_CANCELED) {
                continue;
            }

            $subtotal = $subtotal->add($item->subtotalRupiah());
        }

        try {
            $tax = $this->taxInputCalculator->calculate(
                is_scalar($taxInput) ? (string) $taxInput : null,
                $subtotal->amount(),
            );
        } catch (InvalidArgumentException $e) {
            throw new DomainException($e->getMessage());
        }

        $note->syncNoteTax(
            $subtotal,
            $tax->taxInput(),
            $tax->taxMode(),
            $tax->taxRateBasisPoints(),
            Money::fromInt($tax->taxAmountRupiah()),
        );
    }
}
