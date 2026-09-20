<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;

final class RecordSelectedRowsRefundPlanAuditRecorder
{
    public function __construct(
        private readonly AuditLogPort $audit,
        private readonly AuditEventWriterPort $events,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {}

    public function record(
        SelectedRowsRefundPlan $plan,
        string $actorId,
        string $actorRole,
        string $reason,
        array $processed,
        array $finalizedData,
    ): void {
        $metadata = [
            'note_id' => $plan->noteId(),
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'reason' => trim($reason),
            'selected_row_ids' => $plan->selectedRowIds(),
            'unpaid_row_ids' => $plan->unpaidRowIds(),
            'cancellable_row_ids' => $plan->cancellableRowIds(),
            'refund_ids' => $processed['refund_ids'],
            'allocation_count' => $processed['allocation_count'],
            'total_refund_rupiah' => $plan->totalRefundRupiah(),
            'final_note_state' => $finalizedData['note_state'] ?? null,
        ];
        $this->audit->record('selected_rows_refund_plan_recorded', $metadata);
        $this->events->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'payment',
            aggregateType: 'note',
            aggregateId: $plan->noteId(),
            eventName: 'selected_rows_refund_plan_recorded',
            actorId: $actorId,
            actorRole: $actorRole,
            reason: trim($reason),
            sourceChannel: null,
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: $metadata,
        ));
    }
}
