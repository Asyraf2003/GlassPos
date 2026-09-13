<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Payment\DTO\PayableNoteComponent;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;
use App\Core\Shared\Exceptions\DomainException;

final class ResolveNotePaymentTargetComponents
{
    public function __construct(
        private readonly NoteCurrentRevisionResolver $currentRevision,
        private readonly ResolveNotePayableComponents $components,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PayableNoteComponent>
     */
    public function resolve(Note $note, array $selectedRowIds): array
    {
        $resolved = $selectedRowIds === []
            ? $this->components->fromNote($note)
            : $this->components->fromSelectedRows($note, $selectedRowIds);

        if (! $this->currentRevision->hasRevision($note->id())) {
            return $resolved;
        }

        $revision = $this->currentRevision->resolveOrFail($note->id());
        $this->assertRevisionBelongsToNote($note, $revision);
        $current = $this->filterToCurrentRevision($resolved, $revision);

        if ($selectedRowIds !== [] && count($current) !== count($resolved)) {
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
