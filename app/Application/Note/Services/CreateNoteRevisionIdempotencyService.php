<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\UseCases\CreateNoteRevisionResult;
use App\Ports\Out\IdempotencyRecordPort;

final class CreateNoteRevisionIdempotencyService
{
    private const OPERATION = 'create_note_revision';

    public function __construct(
        private readonly IdempotencyRecordPort $records,
        private readonly CreateTransactionWorkspaceIdempotencyScopeResolver $scopes,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function replay(array $payload): ?CreateNoteRevisionResult
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return null;
        }

        $record = $this->records->find($scope['actor_id'], self::OPERATION, $scope['key']);

        if ($record === null) {
            return null;
        }

        if ($record['request_hash'] !== $scope['hash']) {
            return CreateNoteRevisionResult::failure('Idempotency key revisi sudah dipakai untuk payload berbeda.', [
                'idempotency_key' => ['IDEMPOTENCY_KEY_PAYLOAD_MISMATCH'],
            ]);
        }

        if ($record['status'] !== 'succeeded') {
            return CreateNoteRevisionResult::failure('Revisi nota sedang diproses. Coba ulang beberapa saat lagi.', [
                'idempotency_key' => ['IDEMPOTENCY_KEY_PROCESSING'],
            ]);
        }

        return CreateNoteRevisionResult::success(
            $record['result_payload']['data'] ?? [],
            'Revisi nota sudah diproses sebelumnya.',
        );
    }

    /** @param array<string, mixed> $payload */
    public function start(array $payload): void
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return;
        }

        $this->records->createProcessing(
            $scope['actor_id'],
            self::OPERATION,
            $scope['key'],
            $scope['hash'],
        );
    }

    /** @param array<string, mixed> $payload */
    public function succeed(array $payload, string $noteRootId, CreateNoteRevisionResult $result): void
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return;
        }

        $this->records->markSucceeded(
            $scope['actor_id'],
            self::OPERATION,
            $scope['key'],
            ['data' => $result->data(), 'message' => $result->message()],
            $noteRootId,
        );
    }
}
