<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Mappers;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use stdClass;

final class NoteMapper
{
    /** @param list<WorkItem> $items */
    public static function map(stdClass $row, array $items): Note
    {
        $customerPhone = property_exists($row, 'customer_phone')
            ? ($row->customer_phone === null ? null : (string) $row->customer_phone)
            : null;

        $closedAt = property_exists($row, 'closed_at') && $row->closed_at !== null
            ? new DateTimeImmutable((string) $row->closed_at)
            : null;

        $reopenedAt = property_exists($row, 'reopened_at') && $row->reopened_at !== null
            ? new DateTimeImmutable((string) $row->reopened_at)
            : null;

        $dueDate = property_exists($row, 'due_date') && $row->due_date !== null
            ? new DateTimeImmutable((string) $row->due_date)
            : null;

        $operationalNote = property_exists($row, 'operational_note') && $row->operational_note !== null
            ? (string) $row->operational_note
            : null;

        $noteTaxAmount = Money::fromInt((int) ($row->note_tax_amount_rupiah ?? 0));

        return Note::rehydrate(
            (string) $row->id,
            (string) $row->customer_name,
            $customerPhone,
            new DateTimeImmutable((string) $row->transaction_date),
            self::resolveMappedTotal($row, $items),
            $items,
            property_exists($row, 'note_state') ? (string) $row->note_state : Note::STATE_OPEN,
            $closedAt,
            property_exists($row, 'closed_by_actor_id')
                ? ($row->closed_by_actor_id === null ? null : (string) $row->closed_by_actor_id)
                : null,
            $reopenedAt,
            property_exists($row, 'reopened_by_actor_id')
                ? ($row->reopened_by_actor_id === null ? null : (string) $row->reopened_by_actor_id)
                : null,
            $dueDate,
            $operationalNote,
            self::resolveSubtotalBeforeNoteTax($row, $items, $noteTaxAmount),
            property_exists($row, 'note_tax_input') && $row->note_tax_input !== null ? (string) $row->note_tax_input : null,
            property_exists($row, 'note_tax_mode') && $row->note_tax_mode !== null ? (string) $row->note_tax_mode : Note::TAX_MODE_NONE,
            property_exists($row, 'note_tax_rate_basis_points') && $row->note_tax_rate_basis_points !== null ? (int) $row->note_tax_rate_basis_points : null,
            $noteTaxAmount,
        );
    }

    /** @param list<WorkItem> $items */
    private static function resolveMappedTotal(stdClass $row, array $items): Money
    {
        if (property_exists($row, 'total_rupiah')) {
            return Money::fromInt((int) $row->total_rupiah);
        }

        return self::resolveWorkItemsSubtotal($items);
    }

    /** @param list<WorkItem> $items */
    private static function resolveSubtotalBeforeNoteTax(stdClass $row, array $items, Money $noteTaxAmount): Money
    {
        if (property_exists($row, 'subtotal_before_note_tax_rupiah')) {
            return Money::fromInt((int) $row->subtotal_before_note_tax_rupiah);
        }

        if ($items !== []) {
            return self::resolveWorkItemsSubtotal($items);
        }

        return Money::fromInt(max((int) ($row->total_rupiah ?? 0) - $noteTaxAmount->amount(), 0));
    }

    /** @param list<WorkItem> $items */
    private static function resolveWorkItemsSubtotal(array $items): Money
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
