<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueAuditEventFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueDispositionFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueGuard;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use DateTimeImmutable;

final class AutoSettleNoteRevisionSurplusRefundDueRecorder
{
    private const SOURCE_CHANNEL = 'note_revision_auto_surplus_refund';

    public function __construct(
        private readonly NoteRevisionSurplusDispositionReaderPort $dispositionReader,
        private readonly NoteRevisionSurplusDispositionWriterPort $dispositionWriter,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly CreateNoteRevisionSurplusRefundDueGuard $dueGuard,
        private readonly CreateNoteRevisionSurplusRefundDueDispositionFactory $dueFactory,
        private readonly CreateNoteRevisionSurplusRefundDueAuditEventFactory $dueAuditFactory,
    ) {
    }

    public function record(
        NoteRevisionSettlement $settlement,
        string $actorId,
        string $reason,
        DateTimeImmutable $effectiveAt,
        string $correlationId,
    ): NoteRevisionSurplusDisposition {
        $pending = $this->dueGuard->pendingOrFail(
            $this->dispositionReader->findPendingBySettlementIdForUpdate($settlement->id),
        );
        $command = new CreateNoteRevisionSurplusRefundDueCommand(
            noteRevisionSettlementId: $settlement->id,
            amountRupiah: $pending->unresolvedPendingRupiah,
            reason: $reason,
            actorId: $actorId,
            actorRole: 'admin',
            occurredAt: $settlement->createdAt,
            sourceChannel: self::SOURCE_CHANNEL,
            requestId: sprintf('auto-refund-due:%s', $settlement->id),
            correlationId: $correlationId,
        );

        $dueReason = $this->dueGuard->assertCommandAllowed($command);
        $this->dueGuard->assertAmountFits($command->amountRupiah, $pending);

        $disposition = $this->dueFactory->create($command, $pending);
        $this->auditWriter->write($this->dueAuditFactory->create(
            $disposition->auditEventId,
            $disposition,
            $pending,
            $command->actorId,
            $command->actorRole,
            $dueReason,
            $command->sourceChannel,
            $command->requestId,
            $command->correlationId,
        ));
        $this->dispositionWriter->create($disposition);

        return $disposition;
    }
}
