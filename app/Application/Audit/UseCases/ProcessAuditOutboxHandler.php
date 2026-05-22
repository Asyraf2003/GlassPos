<?php

declare(strict_types=1);

namespace App\Application\Audit\UseCases;

use App\Adapters\Out\Audit\DatabaseAuditEventWriterAdapter;
use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class ProcessAuditOutboxHandler
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_PROCESSED = 'processed';
    private const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly DatabaseAuditEventWriterAdapter $materializer,
        private readonly ClockPort $clock,
    ) {
    }

    /**
     * @return array{processed: int, failed: int, skipped: int}
     */
    public function handle(int $limit, bool $retryFailed, int $maxAttempts): array
    {
        $limit = max(1, $limit);
        $maxAttempts = max(1, $maxAttempts);
        $now = $this->clock->now();

        $rows = DB::table('audit_outbox')
            ->where(static function ($query) use ($retryFailed): void {
                $query->where('status', self::STATUS_PENDING);

                if ($retryFailed) {
                    $query->orWhere('status', self::STATUS_FAILED);
                }
            })
            ->where('attempts', '<', $maxAttempts)
            ->where(static function ($query) use ($now): void {
                $query
                    ->whereNull('available_at')
                    ->orWhere('available_at', '<=', $now);
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $summary = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($rows as $row) {
            try {
                $result = DB::transaction(function () use ($row): string {
                    $now = $this->clock->now();

                    $claimed = DB::table('audit_outbox')
                        ->where('id', (string) $row->id)
                        ->where('status', (string) $row->status)
                        ->update([
                            'status' => self::STATUS_PROCESSING,
                            'locked_at' => $now,
                            'updated_at' => $now,
                        ]);

                    if ($claimed !== 1) {
                        return 'skipped';
                    }

                    $fresh = DB::table('audit_outbox')
                        ->where('id', (string) $row->id)
                        ->first();

                    if ($fresh === null) {
                        throw new RuntimeException('audit_outbox row disappeared during processing.');
                    }

                    $this->materializer->write($this->eventFromRow($fresh));

                    DB::table('audit_outbox')
                        ->where('id', (string) $fresh->id)
                        ->update([
                            'status' => self::STATUS_PROCESSED,
                            'locked_at' => null,
                            'processed_at' => $now,
                            'updated_at' => $now,
                        ]);

                    return 'processed';
                });
            } catch (Throwable $e) {
                $this->markFailure((string) $row->id, $e, $maxAttempts);
                $summary['failed']++;

                continue;
            }

            if ($result === 'processed') {
                $summary['processed']++;
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    private function markFailure(string $rowId, Throwable $e, int $maxAttempts): void
    {
        $row = DB::table('audit_outbox')->where('id', $rowId)->first();

        if ($row === null) {
            return;
        }

        $attempts = ((int) $row->attempts) + 1;
        $now = $this->clock->now();

        DB::table('audit_outbox')
            ->where('id', $rowId)
            ->update([
                'status' => $attempts >= $maxAttempts ? self::STATUS_FAILED : self::STATUS_PENDING,
                'attempts' => $attempts,
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
                'locked_at' => null,
                'updated_at' => $now,
            ]);
    }

    private function eventFromRow(object $row): AuditEventWrite
    {
        return new AuditEventWrite(
            id: (string) $row->audit_event_id,
            boundedContext: (string) $row->bounded_context,
            aggregateType: (string) $row->aggregate_type,
            aggregateId: (string) $row->aggregate_id,
            eventName: (string) $row->event_name,
            actorId: $this->nullableString($row->actor_id),
            actorRole: $this->nullableString($row->actor_role),
            reason: $this->nullableString($row->reason),
            sourceChannel: $this->nullableString($row->source_channel),
            requestId: $this->nullableString($row->request_id),
            correlationId: $this->nullableString($row->correlation_id),
            occurredAt: new DateTimeImmutable((string) $row->occurred_at),
            metadata: $this->metadataFromRow($row->metadata_json),
            snapshots: $this->snapshotsFromRow($row->snapshots_json),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFromRow(mixed $json): array
    {
        if ($json === null || trim((string) $json) === '') {
            return [];
        }

        $decoded = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('audit_outbox metadata_json must decode to an array.');
        }

        return $decoded;
    }

    /**
     * @return list<AuditEventSnapshotWrite>
     */
    private function snapshotsFromRow(mixed $json): array
    {
        if ($json === null || trim((string) $json) === '') {
            return [];
        }

        $decoded = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('audit_outbox snapshots_json must decode to an array.');
        }

        $snapshots = [];

        foreach ($decoded as $snapshot) {
            if (! is_array($snapshot)) {
                throw new RuntimeException('audit_outbox snapshot entry must be an array.');
            }

            $payload = $snapshot['payload'] ?? null;

            if (! is_array($payload)) {
                throw new RuntimeException('audit_outbox snapshot payload must be an array.');
            }

            $snapshots[] = new AuditEventSnapshotWrite(
                (string) ($snapshot['snapshot_kind'] ?? ''),
                $payload,
            );
        }

        return $snapshots;
    }
}
