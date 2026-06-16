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
}
