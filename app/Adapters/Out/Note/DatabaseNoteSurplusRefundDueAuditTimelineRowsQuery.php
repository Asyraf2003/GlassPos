<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use Illuminate\Support\Facades\DB;

final class DatabaseNoteSurplusRefundDueAuditTimelineRowsQuery
{
    public function find(string $noteRootId, int $limit): array
    {
        return DB::table('note_revision_surplus_dispositions')
            ->join('audit_events', 'audit_events.id', '=', 'note_revision_surplus_dispositions.audit_event_id')
            ->where('note_revision_surplus_dispositions.note_root_id', trim($noteRootId))
            ->where('audit_events.event_name', 'note_revision_surplus_refund_due_created')
            ->orderByDesc('audit_events.occurred_at')
            ->orderByDesc('audit_events.id')
            ->limit($limit)
            ->get([
                'audit_events.id as event_id',
                'audit_events.event_name',
                'audit_events.actor_id',
                'audit_events.actor_role',
                'audit_events.reason',
                'audit_events.occurred_at',
                'note_revision_surplus_dispositions.id as disposition_id',
                'note_revision_surplus_dispositions.note_revision_settlement_id',
                'note_revision_surplus_dispositions.note_revision_id',
                'note_revision_surplus_dispositions.disposition_type',
                'note_revision_surplus_dispositions.amount_rupiah',
                'note_revision_surplus_dispositions.before_pending_rupiah',
                'note_revision_surplus_dispositions.after_pending_rupiah',
            ])
            ->map(static fn (object $row): array => [
                'event_id' => (string) $row->event_id,
                'event_name' => (string) $row->event_name,
                'disposition_id' => (string) $row->disposition_id,
                'refund_payment_id' => null,
                'note_revision_settlement_id' => (string) $row->note_revision_settlement_id,
                'note_revision_id' => (string) $row->note_revision_id,
                'disposition_type' => (string) $row->disposition_type,
                'amount_rupiah' => (int) $row->amount_rupiah,
                'before_pending_rupiah' => (int) $row->before_pending_rupiah,
                'after_pending_rupiah' => (int) $row->after_pending_rupiah,
                'refund_due_rupiah' => (int) $row->amount_rupiah,
                'active_refund_paid_rupiah' => 0,
                'remaining_refund_due_rupiah' => (int) $row->amount_rupiah,
                'effective_date' => null,
                'actor_id' => $row->actor_id !== null ? (string) $row->actor_id : null,
                'actor_role' => $row->actor_role !== null ? (string) $row->actor_role : null,
                'reason' => $row->reason !== null ? (string) $row->reason : null,
                'occurred_at' => (string) $row->occurred_at,
            ])
            ->all();
    }
}
