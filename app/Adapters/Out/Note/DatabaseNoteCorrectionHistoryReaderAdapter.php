<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Adapters\Out\Note\Mappers\NoteCorrectionHistoryRowMapper;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use Illuminate\Support\Facades\DB;
use JsonException;

final class DatabaseNoteCorrectionHistoryReaderAdapter implements NoteCorrectionHistoryReaderPort
{
    public function __construct(private readonly NoteCorrectionHistoryRowMapper $rows) {}

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

        return array_map(fn (object $row): array => $this->rows->map($row, $snapshots[(string) $row->id] ?? []), $events);
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
