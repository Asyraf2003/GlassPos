<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\UuidPort;
use Illuminate\Support\Facades\DB;

final class DatabaseAuditEventWriterAdapter implements AuditEventWriterPort
{
    public function __construct(
        private readonly UuidPort $uuid,
    ) {
    }

    public function write(AuditEventWrite $event): void
    {
        DB::table('audit_events')->insert([
            'id' => $event->id(),
            'bounded_context' => $event->boundedContext(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'event_name' => $event->eventName(),
            'actor_id' => $event->actorId(),
            'actor_role' => $event->actorRole(),
            'reason' => $event->reason(),
            'source_channel' => $event->sourceChannel(),
            'request_id' => $event->requestId(),
            'correlation_id' => $event->correlationId(),
            'occurred_at' => $event->occurredAt(),
            'metadata_json' => $this->nullableJson($event->metadata()),
        ]);

        $snapshotRows = [];

        foreach ($event->snapshots() as $snapshot) {
            $snapshotRows[] = [
                'id' => $this->uuid->generate(),
                'audit_event_id' => $event->id(),
                'snapshot_kind' => $snapshot->kind(),
                'payload_json' => $this->requiredJson($snapshot->payload()),
                'created_at' => $event->occurredAt(),
            ];
        }

        if ($snapshotRows !== []) {
            DB::table('audit_event_snapshots')->insert($snapshotRows);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nullableJson(array $payload): ?string
    {
        if ($payload === []) {
            return null;
        }

        return $this->requiredJson($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredJson(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
