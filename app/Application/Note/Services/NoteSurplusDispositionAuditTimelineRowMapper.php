<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class NoteSurplusDispositionAuditTimelineRowMapper
{
    public function map(array $row): array
    {
        $eventName = (string) $row['event_name'];

        return [
            'label' => $this->label($eventName),
            'remaining_label' => $this->remainingLabel($eventName),
            'remaining_rupiah' => $this->remainingRupiah($eventName, $row),
            'event_name' => $eventName,
            'disposition_id' => (string) $row['disposition_id'],
            'refund_payment_id' => $this->nullableString($row['refund_payment_id']),
            'note_revision_settlement_id' => (string) $row['note_revision_settlement_id'],
            'note_revision_id' => (string) $row['note_revision_id'],
            'disposition_type' => (string) $row['disposition_type'],
            'amount_rupiah' => (int) $row['amount_rupiah'],
            'before_pending_rupiah' => (int) $row['before_pending_rupiah'],
            'after_pending_rupiah' => (int) $row['after_pending_rupiah'],
            'refund_due_rupiah' => (int) $row['refund_due_rupiah'],
            'active_refund_paid_rupiah' => (int) $row['active_refund_paid_rupiah'],
            'remaining_refund_due_rupiah' => (int) $row['remaining_refund_due_rupiah'],
            'effective_date' => $this->nullableString($row['effective_date']),
            'actor_id' => $row['actor_id'],
            'actor_role' => $row['actor_role'],
            'reason' => $row['reason'],
            'occurred_at' => (string) $row['occurred_at'],
        ];
    }

    private function label(string $eventName): string
    {
        return match ($eventName) {
            'note_revision_surplus_refund_paid_recorded' => 'Pengembalian Sudah Dibayar Dicatat',
            default => 'Pengembalian Belum Dibayar Ditandai',
        };
    }

    private function remainingLabel(string $eventName): string
    {
        return match ($eventName) {
            'note_revision_surplus_refund_paid_recorded' => 'Sisa pengembalian belum dibayar',
            default => 'Sisa pending',
        };
    }

    private function remainingRupiah(string $eventName, array $row): int
    {
        return match ($eventName) {
            'note_revision_surplus_refund_paid_recorded' => (int) $row['remaining_refund_due_rupiah'],
            default => (int) $row['after_pending_rupiah'],
        };
    }

    private function nullableString(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }
}
