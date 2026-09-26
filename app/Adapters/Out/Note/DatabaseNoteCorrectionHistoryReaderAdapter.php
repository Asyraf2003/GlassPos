<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use Illuminate\Support\Facades\DB;
use JsonException;

final class DatabaseNoteCorrectionHistoryReaderAdapter implements NoteCorrectionHistoryReaderPort
{
    public function isUnrestoredCancellation(string $noteId, string $cancellationEventId): bool
    {
        $exists = DB::table('note_mutation_events')
            ->where('note_id', trim($noteId))
            ->where('mutation_type', 'note_cancelled')
            ->where('id', trim($cancellationEventId))
            ->exists();
        if (! $exists) {
            return false;
        }

        $restoredSnapshots = DB::table('note_mutation_events as events')
            ->join('note_mutation_snapshots as snapshots', 'snapshots.note_mutation_event_id', '=', 'events.id')
            ->where('events.note_id', trim($noteId))
            ->where('events.mutation_type', 'note_restored')
            ->where('snapshots.snapshot_kind', 'after')
            ->get(['snapshots.payload_json']);
        foreach ($restoredSnapshots as $snapshot) {
            $payload = $this->decode((string) $snapshot->payload_json);
            if (($payload['meta']['cancellation_event_id'] ?? null) === trim($cancellationEventId)) {
                return false;
            }
        }

        return true;
    }

    public function findLatestNoteCorrections(string $noteId, int $limit = 10): array
    {
        $events = DB::table('note_mutation_events')
            ->where('note_id', trim($noteId))
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get(['id', 'mutation_type', 'actor_id', 'reason', 'occurred_at'])
            ->all();

        $snapshots = $this->snapshotMap(array_map(static fn (object $row): string => (string) $row->id, $events));

        return array_map(fn (object $row): array => $this->mapRow($row, $snapshots[(string) $row->id] ?? []), $events);
    }

    private function mapRow(object $row, array $snapshots): array
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

    /**
     * @param  list<string>  $eventIds
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function snapshotMap(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $map = [];
        $rows = DB::table('note_mutation_snapshots')
            ->whereIn('note_mutation_event_id', $eventIds)
            ->get(['note_mutation_event_id', 'snapshot_kind', 'payload_json']);

        foreach ($rows as $row) {
            $map[(string) $row->note_mutation_event_id][(string) $row->snapshot_kind] = $this->decode((string) $row->payload_json);
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }
}
