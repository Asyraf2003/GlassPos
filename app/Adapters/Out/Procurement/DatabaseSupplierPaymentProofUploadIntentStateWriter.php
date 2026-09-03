<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofUploadIntentStateWriter
{
    private const INTENTS = 'supplier_payment_proof_upload_intents';
    private const FILES = 'supplier_payment_proof_upload_intent_files';

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

    /** @param array<string,mixed> $resultPayload */
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
}
