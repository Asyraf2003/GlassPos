<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;

final class CancelSelectedRowsAndSyncActiveNoteTotal
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly SelectedActiveWorkItemsResolver $selected,
        private readonly WorkItemStatusTransitionService $transitions,
        private readonly WorkItemWriterPort $workItems,
        private readonly NoteWriterPort $noteWriter,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly ClockPort $clock,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function execute(
        string $noteId,
        array $selectedRowIds,
        string $actorId,
        string $actorRole,
        string $reason,
    ): Result {
        try {
            $note = $this->notes->getById(trim($noteId));

            if ($note === null) {
                return Result::failure('Nota tidak ditemukan.', ['refund' => ['REFUND_INVALID_TARGET']]);
            }

            if (trim($actorId) === '') {
                return Result::failure('Actor wajib ada.', ['refund' => ['ACTOR_REQUIRED']]);
            }

            if (trim($reason) === '') {
                return Result::failure('Alasan refund wajib diisi.', ['refund' => ['AUDIT_REASON_REQUIRED']]);
            }

            $resolved = $this->selected->resolve($note, $selectedRowIds);
            $before = $this->snapshots->build($note);

            foreach ($resolved['selected_items'] as $item) {
                $updated = $this->transitions->findAndApplyById($note, $item->id(), WorkItem::STATUS_CANCELED);
                $this->workItems->updateStatus($updated);
            }

            $note->replaceWorkItems($resolved['remaining_items']);
            $note->syncTotalRupiah($note->totalRupiah());
            $this->noteWriter->updateTotal($note);

            $occurredAt = $this->clock->now();
            $this->timeline->record(
                $note->id(),
                'note_rows_canceled_via_refund',
                trim($actorId),
                trim($actorRole),
                trim($reason),
                $occurredAt,
                $before,
                $this->snapshots->build($note),
                null,
                null,
                [
                    'selected_row_ids' => $resolved['selected_row_ids'],
                    'active_total_rupiah' => $note->totalRupiah()->amount(),
                ],
            );

            return Result::success([
                'note_id' => $note->id(),
                'selected_row_ids' => $resolved['selected_row_ids'],
                'active_total_rupiah' => $note->totalRupiah()->amount(),
            ], 'Rincian pengembalian berhasil dinonaktifkan dari nota aktif.');
        } catch (DomainException $e) {
            return Result::failure($e->getMessage(), ['refund' => ['CANCEL_SELECTED_ROWS_FAILED']]);
        }
    }
}
