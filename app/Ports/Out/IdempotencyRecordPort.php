<?php

declare(strict_types=1);

namespace App\Ports\Out;

interface IdempotencyRecordPort
{
    /**
     * @return array{
     *   status:string,
     *   request_hash:string,
     *   result_note_id:?string,
     *   result_payload:?array<string,mixed>
     * }|null
     */
    public function find(string $actorId, string $operation, string $key): ?array;

    public function createProcessing(
        string $actorId,
        string $operation,
        string $key,
        string $requestHash
    ): void;

    /**
     * @param array<string,mixed> $resultPayload
     */
    public function markSucceeded(
        string $actorId,
        string $operation,
        string $key,
        array $resultPayload,
        ?string $resultNoteId
    ): void;
}
