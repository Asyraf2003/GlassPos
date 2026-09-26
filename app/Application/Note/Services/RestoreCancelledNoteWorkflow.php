<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\UseCases\CreateNoteRevisionWorkflow;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteRevisionReaderPort;
use App\Ports\Out\Note\NoteWriterPort;

final class RestoreCancelledNoteWorkflow
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteWriterPort $noteWriter,
        private readonly NoteCurrentRevisionResolver $revisions,
        private readonly NoteRevisionWorkspaceExistingItemMapper $revisionItems,
        private readonly NoteRevisionReaderPort $revisionRecords,
        private readonly NoteCancellationAccess $access,
        private readonly NoteCorrectionHistoryReaderPort $history,
        private readonly CreateNoteRevisionWorkflow $revisionWorkflow,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly ClockPort $clock,
        private readonly NoteRestoreAuditRecorder $audit,
    ) {}

    public function execute(array $command, string $role): Result
    {
        $root = $this->notes->getByIdForUpdate($command['_note_root_id']);
        if ($root === null) {
            throw new DomainException('NOTE_NOT_FOUND');
        }
        $this->access->assertDateWindow($root->id(), $role);
        if (! $root->isCancelled()) {
            throw new DomainException('NOTE_NOT_CANCELLED');
        }
        $current = $this->revisions->resolveOrFail($root->id());
        if ($current->id() !== $command['base_revision_id']) {
            throw new DomainException('STALE_REVISION');
        }
        if (! $this->history->isUnrestoredCancellation($root->id(), $command['cancellation_event_id'])) {
            throw new DomainException('STALE_CANCELLATION');
        }
        $source = $this->revisionRecords->findById($command['source_revision_id']);
        if ($source === null || $source->noteRootId() !== $root->id() || $source->revisionNumber() > $current->revisionNumber()) {
            throw new DomainException('RESTORE_SOURCE_REVISION_INVALID');
        }

        foreach ($source->lines() as $line) {
            if ($line->transactionType() === 'service_with_external_purchase') {
                throw new DomainException('RESTORE_EXTERNAL_PURCHASE_UNSUPPORTED');
            }
        }
        $before = $this->snapshots->build($root);
        $at = $this->clock->now();
        $payload = [
            'base_revision_id' => $current->id(),
            'reason' => $command['reason'],
            'note' => [
                'customer_name' => $source->customerName(),
                'customer_phone' => $source->customerPhone(),
                'transaction_date' => $source->transactionDate()->format('Y-m-d'),
                'operational_note' => $root->operationalNote(),
            ],
            'items' => $this->revisionItems->mapMany($source),
            'inline_payment' => ['decision' => 'skip'],
        ];
        $root->beginRestoreAsAcceptedRevision();
        $this->noteWriter->updateOperationalState($root);
        $revisionResult = $this->revisionWorkflow->execute($root->id(), $payload, $command['_actor_id'], false, $source);
        if ($revisionResult->isFailure()) {
            throw new DomainException($revisionResult->message() ?? 'NOTE_RESTORE_REVISION_FAILED');
        }
        $after = $this->notes->getByIdForUpdate($root->id()) ?? throw new DomainException('NOTE_NOT_FOUND');
        $metadata = [
            'note_id' => $root->id(),
            'cancellation_event_id' => $command['cancellation_event_id'],
            'base_revision_id' => $current->id(),
            'source_revision_id' => $source->id(),
            'restored_revision_id' => $revisionResult->data()['revision_id'],
            'restored_revision_number' => $revisionResult->data()['revision_number'],
        ];
        $eventId = $this->audit->record($after, $command, $role, $at, $before, $metadata);

        return Result::success([
            'note_id' => $root->id(),
            'restore_event_id' => $eventId,
            'cancellation_event_id' => $command['cancellation_event_id'],
            'source_revision_id' => $source->id(),
            'revision_id' => $revisionResult->data()['revision_id'],
            'revision_number' => $revisionResult->data()['revision_number'],
            'note_state' => $after->noteState(),
        ], 'Transaksi dipulihkan sebagai revisi baru.');
    }
}
