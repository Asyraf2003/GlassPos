<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Payment\DTO\PayableNoteComponent;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;

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
     * @return list<PayableNoteComponent>
     */
    public function fromCurrentRevision(Note $note, NoteRevision $revision): array
    {
        $this->assertRevisionBelongsToNote($note, $revision);

        return $this->filterToCurrentRevision(
            $this->fromNote($note),
            $revision,
        );
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PayableNoteComponent>
     */
    public function fromSelectedRows(Note $note, array $selectedRowIds): array
    {
        return $this->selectedRows->resolve($note, $selectedRowIds);
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PayableNoteComponent>
     */
    public function fromSelectedRowsCurrentRevision(
        Note $note,
        array $selectedRowIds,
        NoteRevision $revision,
    ): array {
        $this->assertRevisionBelongsToNote($note, $revision);

        $selected = $this->fromSelectedRows($note, $selectedRowIds);
        $current = $this->filterToCurrentRevision($selected, $revision);

        if (count($current) !== count($selected)) {
            throw new DomainException('Billing row pembayaran yang dipilih tidak valid untuk revisi aktif nota ini.');
        }

        return $current;
    }

    /**
     * @param list<PayableNoteComponent> $components
     * @return list<PayableNoteComponent>
     */
    private function filterToCurrentRevision(array $components, NoteRevision $revision): array
    {
        $currentWorkItemIds = [];

        foreach ($revision->lines() as $line) {
            $workItemId = $line->workItemRootId();

            if ($workItemId !== null && trim($workItemId) !== '') {
                $currentWorkItemIds[$workItemId] = true;
            }
        }

        return array_values(array_filter(
            $components,
            static fn (PayableNoteComponent $component): bool => isset(
                $currentWorkItemIds[$component->workItemId()]
            ),
        ));
    }

    private function assertRevisionBelongsToNote(Note $note, NoteRevision $revision): void
    {
        if ($revision->noteRootId() !== $note->id()) {
            throw new DomainException('Current revision tidak sesuai dengan target note pembayaran.');
        }
    }
}
