<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Core\Note\Revision\NoteRevision;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteRevisionWriterPort;
use App\Ports\Out\Note\NoteRevisionSettlementWriterPort;

final class CreateNoteRevisionCommitter
{
    public function __construct(
        private readonly NoteRevisionWriterPort $revisionWriter,
        private readonly NoteRevisionSettlementWriterPort $settlements,
        private readonly CreateNoteRevisionAuditPayloadBuilder $auditPayloads,
        private readonly AuditLogPort $audit,
        private readonly CreateNoteRevisionDurableAudit $durableAudit,
    ) {
    }

    public function commit(
        string $noteRootId,
        string $parentRevisionId,
        ?string $actorId,
        string $reason,
        NoteRevision $revision,
        ?NoteRevisionSettlement $settlement = null,
    ): CreateNoteRevisionResult {
        $this->revisionWriter->create($revision);

        if ($settlement !== null) {
            $this->settlements->create($settlement);
        }

        $this->revisionWriter->setCurrentRevision(
            $noteRootId,
            $revision->id(),
            $revision->revisionNumber(),
        );

        $this->audit->record(
            'note_revision_created',
            $this->auditPayloads->build($noteRootId, $parentRevisionId, $actorId, $reason, $revision),
        );
        $this->durableAudit->record($noteRootId, $parentRevisionId, $actorId, $reason, $revision);

        return CreateNoteRevisionResult::success([
            'note_root_id' => $noteRootId,
            'revision_id' => $revision->id(),
            'revision_number' => $revision->revisionNumber(),
        ], 'Revisi nota berhasil disimpan.');
    }
}
