<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Mappers;

final class NoteCorrectionHistoryRowMapper
{
    public function map(object $row, array $snapshots): array
    {
        $before = is_array($snapshots['before'] ?? null) ? $snapshots['before'] : [];
        $after = is_array($snapshots['after'] ?? null) ? $snapshots['after'] : [];
        $meta = is_array($after['meta'] ?? null) ? $after['meta'] : (is_array($before['meta'] ?? null) ? $before['meta'] : []);

        return [
            'event_label' => $this->eventLabel((string) $row->mutation_type),
            'created_at' => (string) $row->occurred_at,
            'reason' => $row->reason !== null ? (string) $row->reason : null,
            'performed_by_actor_id' => $row->actor_id !== null ? (string) $row->actor_id : null,
            'target_status' => $meta['target_status'] ?? null,
            'refund_required_rupiah' => (int) ($meta['refund_required_rupiah'] ?? 0),
            'before_total_rupiah' => $before['note']['total_rupiah'] ?? null,
            'after_total_rupiah' => $after['note']['total_rupiah'] ?? null,
        ];
    }

    private function eventLabel(string $mutationType): string
    {
        return match ($mutationType) {
            'note_cancelled' => 'Batalkan Transaksi',
            'note_restored' => 'Pulihkan Transaksi',
            'paid_service_only_work_item_corrected' => 'Koreksi Nominal Servis',
            'paid_service_with_store_stock_part_service_fee_only_corrected' => 'Koreksi Biaya Servis + Sparepart Toko',
            'paid_service_with_external_purchase_service_fee_only_corrected' => 'Koreksi Biaya Servis + Sparepart Luar',
            default => 'Koreksi Status Rincian',
        };
    }
}
