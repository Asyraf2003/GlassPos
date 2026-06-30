<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueAuditEventFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueDispositionFactory;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueGuard;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentAuditEventFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentCommand;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentGuard;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use DateTimeImmutable;

final class AutoSettleNoteRevisionSurplusRefund
{
    private const SOURCE_CHANNEL = 'note_revision_auto_surplus_refund';

    public function __construct(
        private readonly NoteRevisionSurplusDispositionReaderPort $dispositionReader,
        private readonly NoteRevisionSurplusDispositionWriterPort $dispositionWriter,
        private readonly NoteRevisionSurplusRefundDueSourceReaderPort $refundDueSources,
        private readonly NoteRevisionSurplusRefundPaymentReaderPort $refundPaymentReader,
        private readonly NoteRevisionSurplusRefundPaymentWriterPort $refundPaymentWriter,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly CreateNoteRevisionSurplusRefundDueGuard $dueGuard,
        private readonly CreateNoteRevisionSurplusRefundDueDispositionFactory $dueFactory,
        private readonly CreateNoteRevisionSurplusRefundDueAuditEventFactory $dueAuditFactory,
        private readonly RecordNoteRevisionSurplusRefundPaymentGuard $paymentGuard,
        private readonly RecordNoteRevisionSurplusRefundPaymentFactory $paymentFactory,
        private readonly RecordNoteRevisionSurplusRefundPaymentAuditEventFactory $paymentAuditFactory,
    ) {
    }

    public function settle(
        NoteRevisionSettlement $settlement,
        ?string $actorId,
        string $reason,
        DateTimeImmutable $effectiveAt,
    ): void {
        if ($settlement->settlementStatus !== NoteRevisionSettlement::STATUS_OVERPAID_PENDING) {
            return;
        }

        if ($settlement->surplusRupiah <= 0) {
            return;
        }

        $actorId = $this->actorId($actorId);
        $reason = $this->reason($reason);
        $correlationId = sprintf('auto-surplus-refund:%s', $settlement->id);

        $pending = $this->dueGuard->pendingOrFail(
            $this->dispositionReader->findPendingBySettlementIdForUpdate($settlement->id),
        );

        $dueCommand = new CreateNoteRevisionSurplusRefundDueCommand(
            noteRevisionSettlementId: $settlement->id,
            amountRupiah: $pending->unresolvedPendingRupiah,
            reason: $reason,
            actorId: $actorId,
            actorRole: 'admin',
            occurredAt: $effectiveAt,
            sourceChannel: self::SOURCE_CHANNEL,
            requestId: sprintf('auto-refund-due:%s', $settlement->id),
            correlationId: $correlationId,
        );

        $dueReason = $this->dueGuard->assertCommandAllowed($dueCommand);
        $this->dueGuard->assertAmountFits($dueCommand->amountRupiah, $pending);

        $disposition = $this->dueFactory->create($dueCommand, $pending);
        $this->auditWriter->write($this->dueAuditFactory->create(
            $disposition->auditEventId,
            $disposition,
            $pending,
            $dueCommand->actorId,
            $dueCommand->actorRole,
            $dueReason,
            $dueCommand->sourceChannel,
            $dueCommand->requestId,
            $dueCommand->correlationId,
        ));
        $this->dispositionWriter->create($disposition);

        $source = $this->paymentGuard->sourceOrFail(
            $this->refundDueSources->findActiveRefundDueByDispositionIdForUpdate($disposition->id),
        );
        $paymentCommand = new RecordNoteRevisionSurplusRefundPaymentCommand(
            noteRevisionSurplusDispositionId: $disposition->id,
            amountRupiah: $source->remainingRefundDueRupiah,
            effectiveDate: $effectiveAt,
            reason: $reason,
            actorId: $actorId,
            actorRole: 'admin',
            idempotencyKey: sprintf('auto-refund-paid:%s', $settlement->id),
            occurredAt: $effectiveAt,
            sourceChannel: self::SOURCE_CHANNEL,
            requestId: sprintf('auto-refund-paid:%s', $settlement->id),
            correlationId: $correlationId,
        );

        $paymentReason = $this->paymentGuard->assertCommandAllowed($paymentCommand);
        $existing = $this->refundPaymentReader->findActiveByDispositionIdAndIdempotencyKey(
            $source->dispositionId,
            $paymentCommand->idempotencyKey,
        );

        if ($existing !== null) {
            $this->paymentGuard->assertRepeatedPayloadMatches($paymentCommand, $existing);

            return;
        }

        $this->paymentGuard->assertAmountFits($paymentCommand->amountRupiah, $source);

        $payment = $this->paymentFactory->create($paymentCommand, $source);
        $this->auditWriter->write($this->paymentAuditFactory->create(
            $payment,
            $source,
            $paymentCommand->actorId,
            $paymentCommand->actorRole,
            $paymentReason,
            $paymentCommand->sourceChannel,
            $paymentCommand->requestId,
            $paymentCommand->correlationId,
        ));
        $this->refundPaymentWriter->create($payment);
    }

    private function actorId(?string $actorId): string
    {
        $actorId = trim((string) $actorId);

        return $actorId === '' ? 'system-note-revision-auto-surplus-refund' : $actorId;
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);

        return $reason === ''
            ? 'Auto refund due and refund paid from downward note revision surplus.'
            : $reason;
    }
}
