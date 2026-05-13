<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusRefundDueSource;
use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;

final class RecordNoteRevisionSurplusRefundPaymentAuditEventFactory
{
    public function create(
        NoteRevisionSurplusRefundPayment $payment,
        NoteRevisionSurplusRefundDueSource $source,
        string $actorId,
        string $actorRole,
        string $reason,
        ?string $sourceChannel,
        ?string $requestId,
        ?string $correlationId,
    ): AuditEventWrite {
        $afterPaid = $source->activeRefundPaidRupiah + $payment->amountRupiah;
        $afterRemaining = $source->remainingRefundDueRupiah - $payment->amountRupiah;

        return new AuditEventWrite(
            id: $payment->auditEventId,
            boundedContext: 'note',
            aggregateType: 'note_revision_surplus_refund_payment',
            aggregateId: $payment->id,
            eventName: 'note_revision_surplus_refund_paid_recorded',
            actorId: trim($actorId),
            actorRole: trim($actorRole),
            reason: trim($reason),
            sourceChannel: $sourceChannel,
            requestId: $requestId,
            correlationId: $correlationId,
            occurredAt: $payment->occurredAt,
            metadata: [
                'note_root_id' => $payment->noteRootId,
                'note_revision_id' => $payment->noteRevisionId,
                'note_revision_settlement_id' => $payment->noteRevisionSettlementId,
                'note_revision_surplus_disposition_id' => $payment->noteRevisionSurplusDispositionId,
                'note_revision_surplus_refund_payment_id' => $payment->id,
                'amount_rupiah' => $payment->amountRupiah,
                'effective_date' => $payment->effectiveDateString(),
                'disposition_type' => NoteRevisionSurplusDisposition::TYPE_REFUND_DUE,
                'idempotency_key' => $payment->idempotencyKey,
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', [
                    'refund_due_rupiah' => $source->refundDueRupiah,
                    'active_refund_paid_rupiah' => $source->activeRefundPaidRupiah,
                    'remaining_refund_due_rupiah' => $source->remainingRefundDueRupiah,
                ]),
                new AuditEventSnapshotWrite('after', [
                    'refund_due_rupiah' => $source->refundDueRupiah,
                    'active_refund_paid_rupiah' => $afterPaid,
                    'remaining_refund_due_rupiah' => $afterRemaining,
                ]),
            ],
        );
    }
}
