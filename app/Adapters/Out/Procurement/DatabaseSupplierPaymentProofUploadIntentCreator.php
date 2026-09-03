<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofUploadIntentCreator
{
    private const INTENTS = 'supplier_payment_proof_upload_intents';
    private const FILES = 'supplier_payment_proof_upload_intent_files';

    /**
     * @param list<array{
     *   id:string,ordinal:int,staging_path:string,original_filename:string,
     *   declared_mime_type:string,declared_size_bytes:int
     * }> $files
     */
    public function createPrepared(
        string $uploadIntentId,
        string $actorId,
        string $scopeType,
        string $scopeId,
        ?string $reservedSupplierPaymentId,
        string $idempotencyKey,
        string $requestHash,
        DateTimeImmutable $expiresAt,
        array $files,
    ): bool {
        return DB::transaction(function () use (
            $uploadIntentId,
            $actorId,
            $scopeType,
            $scopeId,
            $reservedSupplierPaymentId,
            $idempotencyKey,
            $requestHash,
            $expiresAt,
            $files,
        ): bool {
            $inserted = DB::table(self::INTENTS)->insertOrIgnore([
                'id' => $uploadIntentId,
                'actor_id' => $actorId,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'reserved_supplier_payment_id' => $reservedSupplierPaymentId,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'status' => 'prepared',
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted !== 1) {
                return false;
            }

            $rows = array_map(
                fn (array $file): array => $this->fileRow($uploadIntentId, $file),
                $files,
            );

            if ($rows !== []) {
                DB::table(self::FILES)->insert($rows);
            }

            return true;
        });
    }

    /** @param array<string,mixed> $file @return array<string,mixed> */
    private function fileRow(string $uploadIntentId, array $file): array
    {
        return [
            'id' => (string) $file['id'],
            'upload_intent_id' => $uploadIntentId,
            'ordinal' => (int) $file['ordinal'],
            'staging_path' => (string) $file['staging_path'],
            'original_filename' => (string) $file['original_filename'],
            'declared_mime_type' => (string) $file['declared_mime_type'],
            'declared_size_bytes' => (int) $file['declared_size_bytes'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
