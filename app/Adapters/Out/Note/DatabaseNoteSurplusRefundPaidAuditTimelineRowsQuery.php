<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use Illuminate\Support\Facades\DB;

final class DatabaseNoteSurplusRefundPaidAuditTimelineRowsQuery
{
    public function find(string $noteRootId, int $limit): array
    {
        return DB::table('note_revision_surplus_refund_payments')
            ->join('audit_events', 'audit_events.id', '=', 'note_revision_surplus_refund_payments.audit_event_id')
            ->leftJoin('audit_event_snapshots as after_snapshot', function ($join): void {
                $join->on('after_snapshot.audit_event_id', '=', 'audit_events.id')
                    ->where('after_snapshot.snapshot_kind', '=', 'after');
            })
            ->where('note_revision_surplus_refund_payments.note_root_id', trim($noteRootId))
            ->where('note_revision_surplus_refund_payments.status', 'active')
            ->where('audit_events.event_name', 'note_revision_surplus_refund_paid_recorded')
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
                'after_snapshot.payload_json as after_payload_json',
                'note_revision_surplus_refund_payments.id as refund_payment_id',
                'note_revision_surplus_refund_payments.note_revision_surplus_disposition_id as disposition_id',
                'note_revision_surplus_refund_payments.note_revision_settlement_id',
                'note_revision_surplus_refund_payments.note_revision_id',
                'note_revision_surplus_refund_payments.amount_rupiah',
                'note_revision_surplus_refund_payments.effective_date',
            ])
            ->map(static fn (object $row): array => self::mapRow($row))
            ->all();
    }

    private static function mapRow(object $row): array
    {
        $after = self::decodePayload($row->after_payload_json);

        return [
            'event_id' => (string) $row->event_id,
            'event_name' => (string) $row->event_name,
            'disposition_id' => (string) $row->disposition_id,
            'refund_payment_id' => (string) $row->refund_payment_id,
            'note_revision_settlement_id' => (string) $row->note_revision_settlement_id,
            'note_revision_id' => (string) $row->note_revision_id,
            'disposition_type' => 'refund_due',
            'amount_rupiah' => (int) $row->amount_rupiah,
            'before_pending_rupiah' => 0,
            'after_pending_rupiah' => (int) ($after['remaining_refund_due_rupiah'] ?? 0),
            'refund_due_rupiah' => (int) ($after['refund_due_rupiah'] ?? 0),
            'active_refund_paid_rupiah' => (int) ($after['active_refund_paid_rupiah'] ?? 0),
            'remaining_refund_due_rupiah' => (int) ($after['remaining_refund_due_rupiah'] ?? 0),
            'effective_date' => $row->effective_date !== null ? (string) $row->effective_date : null,
            'actor_id' => $row->actor_id !== null ? (string) $row->actor_id : null,
            'actor_role' => $row->actor_role !== null ? (string) $row->actor_role : null,
            'reason' => $row->reason !== null ? (string) $row->reason : null,
            'occurred_at' => (string) $row->occurred_at,
        ];
    }

    private static function decodePayload(mixed $payload): array
    {
        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
