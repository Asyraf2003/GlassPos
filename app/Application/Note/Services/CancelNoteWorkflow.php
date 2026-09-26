<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;

final class CancelNoteWorkflow
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteWriterPort $noteWriter,
        private readonly WorkItemWriterPort $items,
        private readonly NoteCurrentRevisionResolver $revisions,
        private readonly NoteCancellationAccess $access,
        private readonly NoteCancellationEligibility $eligibility,
        private readonly CompensateCancelledNoteInventory $inventory,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly NoteCancellationAudit $audit,
        private readonly NoteHistoryProjectionService $projection,
        private readonly ClockPort $clock,
    ) {}

    public function execute(array $payload, string $role): Result
    {
        $note = $this->notes->getByIdForUpdate($payload['_note_root_id']);
        if ($note === null) {
            throw new DomainException('NOTE_NOT_FOUND');
        }
        $this->access->assertDateWindow($note->id(), $role);
        $current = $this->revisions->resolveOrFail($note->id());
        if ($current->id() !== $payload['base_revision_id']) {
            throw new DomainException('STALE_REVISION');
        }
        $this->eligibility->assertEligible($note);
        $before = $this->snapshots->build($note);
        $at = $this->clock->now();
        $movementIds = $this->inventory->execute($note, $at);
        $note->cancel();
        foreach ($note->workItems() as $item) {
            $this->items->updateStatus($item);
        }
        $this->noteWriter->updateTotal($note);
        $this->noteWriter->updateOperationalState($note);
        $this->projection->syncNote($note->id());
        $eventId = $this->audit->record($note, $before, $payload, $role, $movementIds, $at);

        return Result::success([
            'note_id' => $note->id(),
            'cancellation_id' => $eventId,
            'base_revision_id' => $current->id(),
            'note_state' => $note->noteState(),
        ], 'Transaksi dibatalkan.');
    }
}
