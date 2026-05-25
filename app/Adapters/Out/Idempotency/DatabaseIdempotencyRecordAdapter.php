<?php

declare(strict_types=1);

namespace App\Adapters\Out\Idempotency;

use App\Ports\Out\IdempotencyRecordPort;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

final class DatabaseIdempotencyRecordAdapter implements IdempotencyRecordPort
{
    public function find(string $actorId, string $operation, string $key): ?array
    {
        $row = DB::table('idempotency_records')
            ->where('actor_id', $actorId)
            ->where('operation', $operation)
            ->where('idempotency_key', $key)
            ->first();

        if ($row === null) {
            return null;
        }

        $payload = $row->result_payload_json === null
            ? null
            : json_decode((string) $row->result_payload_json, true, 512, JSON_THROW_ON_ERROR);

        return [
            'status' => (string) $row->status,
            'request_hash' => (string) $row->request_hash,
            'result_note_id' => $row->result_note_id === null ? null : (string) $row->result_note_id,
            'result_payload' => is_array($payload) ? $payload : null,
        ];
    }

    public function createProcessing(
        string $actorId,
        string $operation,
        string $key,
        string $requestHash
    ): void {
        DB::table('idempotency_records')->insert([
            'id' => (string) Str::uuid(),
            'actor_id' => $actorId,
            'operation' => $operation,
            'idempotency_key' => $key,
            'request_hash' => $requestHash,
            'status' => 'processing',
            'locked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function markSucceeded(
        string $actorId,
        string $operation,
        string $key,
        array $resultPayload,
        ?string $resultNoteId
    ): void {
        DB::table('idempotency_records')
            ->where('actor_id', $actorId)
            ->where('operation', $operation)
            ->where('idempotency_key', $key)
            ->update([
                'status' => 'succeeded',
                'response_type' => 'redirect',
                'result_note_id' => $resultNoteId,
                'result_payload_json' => json_encode($resultPayload, JSON_THROW_ON_ERROR),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
