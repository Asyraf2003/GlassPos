<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use JsonException;

final class DatabaseSupplierPaymentProofUploadIntentAdapter implements SupplierPaymentProofUploadIntentPort
{
    private const INTENTS = 'supplier_payment_proof_upload_intents';
    private const FILES = 'supplier_payment_proof_upload_intent_files';

    public function findForPrepare(
        string $actorId,
        string $scopeType,
        string $scopeId,
        string $idempotencyKey,
    ): ?array {
        $row = DB::table(self::INTENTS)
            ->where('actor_id', $actorId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByIdForActor(string $uploadIntentId, string $actorId): ?array
    {
        $row = DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('actor_id', $actorId)
            ->first();

        return $row === null ? null : $this->hydrate($row);
    }

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
                'locked_at' => null,
                'finalized_at' => null,
                'expires_at' => $expiresAt,
                'result_payload_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted !== 1) {
                return false;
            }

            $rows = array_map(
                fn (array $file): array => [
                    'id' => (string) $file['id'],
                    'upload_intent_id' => $uploadIntentId,
                    'ordinal' => (int) $file['ordinal'],
                    'staging_path' => (string) $file['staging_path'],
                    'final_storage_path' => null,
                    'original_filename' => (string) $file['original_filename'],
                    'declared_mime_type' => (string) $file['declared_mime_type'],
                    'declared_size_bytes' => (int) $file['declared_size_bytes'],
                    'verified_mime_type' => null,
                    'verified_size_bytes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $files,
            );

            if ($rows !== []) {
                DB::table(self::FILES)->insert($rows);
            }

            return true;
        });
    }

    public function claimForFinalize(string $uploadIntentId, string $actorId): bool
    {
        return DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('actor_id', $actorId)
            ->where('status', 'prepared')
            ->where('expires_at', '>', now())
            ->update([
                'status' => 'finalizing',
                'locked_at' => now(),
                'updated_at' => now(),
            ]) === 1;
    }

    public function releaseFinalizeClaim(string $uploadIntentId, string $actorId): bool
    {
        return DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('actor_id', $actorId)
            ->where('status', 'finalizing')
            ->update([
                'status' => 'prepared',
                'locked_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    public function recordVerifiedFile(
        string $uploadIntentId,
        string $fileId,
        string $finalStoragePath,
        string $verifiedMimeType,
        int $verifiedSizeBytes,
    ): bool {
        return DB::table(self::FILES)
            ->where('id', $fileId)
            ->where('upload_intent_id', $uploadIntentId)
            ->whereNull('final_storage_path')
            ->update([
                'final_storage_path' => $finalStoragePath,
                'verified_mime_type' => $verifiedMimeType,
                'verified_size_bytes' => $verifiedSizeBytes,
                'updated_at' => now(),
            ]) === 1;
    }

    public function markFinalized(
        string $uploadIntentId,
        string $actorId,
        array $resultPayload,
    ): bool {
        return DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('actor_id', $actorId)
            ->where('status', 'finalizing')
            ->update([
                'status' => 'finalized',
                'locked_at' => null,
                'finalized_at' => now(),
                'result_payload_json' => json_encode($resultPayload, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]) === 1;
    }

    /** @return array<string,mixed> */
    private function hydrate(object $row): array
    {
        $payload = $row->result_payload_json === null
            ? null
            : json_decode((string) $row->result_payload_json, true, 512, JSON_THROW_ON_ERROR);

        $files = DB::table(self::FILES)
            ->where('upload_intent_id', (string) $row->id)
            ->orderBy('ordinal')
            ->get()
            ->map(static fn (object $file): array => [
                'id' => (string) $file->id,
                'ordinal' => (int) $file->ordinal,
                'staging_path' => (string) $file->staging_path,
                'final_storage_path' => $file->final_storage_path === null ? null : (string) $file->final_storage_path,
                'original_filename' => (string) $file->original_filename,
                'declared_mime_type' => (string) $file->declared_mime_type,
                'declared_size_bytes' => (int) $file->declared_size_bytes,
                'verified_mime_type' => $file->verified_mime_type === null ? null : (string) $file->verified_mime_type,
                'verified_size_bytes' => $file->verified_size_bytes === null ? null : (int) $file->verified_size_bytes,
            ])
            ->all();

        return [
            'id' => (string) $row->id,
            'actor_id' => (string) $row->actor_id,
            'scope_type' => (string) $row->scope_type,
            'scope_id' => (string) $row->scope_id,
            'reserved_supplier_payment_id' => $row->reserved_supplier_payment_id === null ? null : (string) $row->reserved_supplier_payment_id,
            'idempotency_key' => (string) $row->idempotency_key,
            'request_hash' => (string) $row->request_hash,
            'status' => (string) $row->status,
            'locked_at' => $row->locked_at,
            'finalized_at' => $row->finalized_at,
            'expires_at' => $row->expires_at,
            'result_payload' => is_array($payload) ? $payload : null,
            'files' => $files,
        ];
    }
}
