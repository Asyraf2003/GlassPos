<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\IdempotencyRecordPort;

final class NoteRestoreIdempotency
{
    private const OPERATION = 'restore_cancelled_note';

    public function __construct(
        private readonly IdempotencyRecordPort $records,
        private readonly CreateTransactionWorkspaceIdempotencyScopeResolver $scopes,
    ) {}

    public function replay(array $payload): ?Result
    {
        $scope = $this->scope($payload);
        $record = $this->records->find($scope['actor_id'], self::OPERATION, $scope['key']);
        if ($record === null) {
            return null;
        }
        if ($record['request_hash'] !== $scope['hash']) {
            return Result::failure('Kunci pemulihan sudah digunakan untuk data berbeda.', ['restore' => ['IDEMPOTENCY_KEY_PAYLOAD_MISMATCH']]);
        }
        if ($record['status'] !== 'succeeded') {
            return Result::failure('Pemulihan sedang diproses.', ['restore' => ['IDEMPOTENCY_KEY_PROCESSING']]);
        }

        return Result::success($record['result_payload']['data'], 'Pemulihan sudah diproses sebelumnya.');
    }

    public function start(array $payload): void
    {
        $scope = $this->scope($payload);
        $this->records->createProcessing($scope['actor_id'], self::OPERATION, $scope['key'], $scope['hash']);
    }

    public function succeed(array $payload, Result $result): void
    {
        $scope = $this->scope($payload);
        $this->records->markSucceeded($scope['actor_id'], self::OPERATION, $scope['key'], ['data' => $result->data()], $payload['_note_root_id']);
    }

    private function scope(array $payload): array
    {
        return $this->scopes->resolve($payload)
            ?? throw new \InvalidArgumentException('Restore idempotency key is required.');
    }
}
