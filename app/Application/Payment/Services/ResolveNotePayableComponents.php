<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Payment\DTO\PayableNoteComponent;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;

final class ResolveNotePayableComponents
{
    public function __construct(
        private readonly ResolveNotePayableComponentsSelectedRows $selectedRows,
    ) {
    }

    /**
     * @return list<PayableNoteComponent>
     */
    public function fromNote(Note $note): array
    {
        $components = [];
        $nextOrder = 1;

        foreach ($note->workItems() as $item) {
            if ($item->status() === WorkItem::STATUS_CANCELED) {
                continue;
            }

            $resolved = PayableComponentsFromWorkItem::resolve($item, $nextOrder);
            $components = [...$components, ...$resolved];
            $nextOrder += count($resolved);
        }

        return $components;
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PayableNoteComponent>
     */
    public function fromSelectedRows(Note $note, array $selectedRowIds): array
    {
        return $this->selectedRows->resolve($note, $selectedRowIds);
    }
}
