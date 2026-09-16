<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\UseCases\CorrectPaidServiceOnlySupportTrait;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\ClockPort;

final class CorrectPaidServiceOnlyWorkItemFinalizer
{
    use CorrectPaidServiceOnlySupportTrait;

    public function __construct(
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly ClockPort $clock,
        private readonly AuditLogPort $audit,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function finalize(
        string $performedByActorId,
        int $lineNo,
        string $reason,
        array $context,
    ): int {
        $note = $context['note'];
        $afterNote = $context['after_note'];
        $before = $context['before'];
        $after = $context['after'];

        // Preserve the correction's generated surplus amount, even after automatic payout.
        $refundReq = (int) $context['refund_required_rupiah'];

        $this->timeline->record(
            $note->id(),
            'paid_service_only_work_item_corrected',
            $performedByActorId,
            'admin',
            $reason,
            $this->clock->now(),
            $before,
            $after,
            null,
            null,
            ['refund_required_rupiah' => $refundReq]
        );

        $this->audit->record(
            'paid_service_only_work_item_corrected',
            $this->formatAuditPayload($performedByActorId, $note->id(), $lineNo, $reason, $refundReq, $before, $after)
        );

        return $refundReq;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function successPayload(array $context, int $refundReq): array
    {
        return $this->formatSuccessPayload(
            $context['after_note'],
            $context['corrected'],
            $refundReq,
        );
    }
}
