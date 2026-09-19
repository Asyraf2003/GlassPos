<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentAuditEventFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentCommand;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentFactory;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentGuard;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use DateTimeImmutable;

final class AutoSettleNoteRevisionSurplusRefundPaymentRecorder
{
    private const SOURCE_CHANNEL = 'note_revision_auto_surplus_refund';

    public function __construct(
        private readonly NoteRevisionSurplusRefundDueSourceReaderPort $refundDueSources,
        private readonly NoteRevisionSurplusRefundPaymentReaderPort $refundPaymentReader,
        private readonly NoteRevisionSurplusRefundPaymentWriterPort $refundPaymentWriter,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly RecordNoteRevisionSurplusRefundPaymentGuard $paymentGuard,
        private readonly RecordNoteRevisionSurplusRefundPaymentFactory $paymentFactory,
        private readonly RecordNoteRevisionSurplusRefundPaymentAuditEventFactory $paymentAuditFactory,
    ) {
    }

    public function record(
        NoteRevisionSettlement $settlement,
        NoteRevisionSurplusDisposition $disposition,
        string $actorId,
        string $reason,
        DateTimeImmutable $effectiveAt,
        string $correlationId,
    ): void {
        $source = $this->paymentGuard->sourceOrFail(
            $this->refundDueSources->findActiveRefundDueByDispositionIdForUpdate($disposition->id),
        );
        $command = new RecordNoteRevisionSurplusRefundPaymentCommand(
            noteRevisionSurplusDispositionId: $disposition->id,
            amountRupiah: $source->remainingRefundDueRupiah,
            effectiveDate: $effectiveAt,
            reason: $reason,
            actorId: $actorId,
            actorRole: 'admin',
            idempotencyKey: sprintf('auto-refund-paid:%s', $settlement->id),
            occurredAt: $settlement->createdAt,
            sourceChannel: self::SOURCE_CHANNEL,
            requestId: sprintf('auto-refund-paid:%s', $settlement->id),
            correlationId: $correlationId,
        );

        $paymentReason = $this->paymentGuard->assertCommandAllowed($command);
        $existing = $this->refundPaymentReader->findActiveByDispositionIdAndIdempotencyKey(
            $source->dispositionId,
            $command->idempotencyKey,
        );

        if ($existing !== null) {
            $this->paymentGuard->assertRepeatedPayloadMatches($command, $existing);

            return;
        }

        $this->paymentGuard->assertAmountFits($command->amountRupiah, $source);

        $payment = $this->paymentFactory->create($command, $source);
        $this->auditWriter->write($this->paymentAuditFactory->create(
            $payment,
            $source,
            $command->actorId,
            $command->actorRole,
            $paymentReason,
            $command->sourceChannel,
            $command->requestId,
            $command->correlationId,
        ));
        $this->refundPaymentWriter->create($payment);
    }
}
