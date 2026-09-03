<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofUploadIntentHydrator
{
    private const FILES = 'supplier_payment_proof_upload_intent_files';

    /** @return array<string,mixed> */
    public function hydrate(object $row): array
    {
        $payload = $row->result_payload_json === null
            ? null
            : json_decode((string) $row->result_payload_json, true, 512, JSON_THROW_ON_ERROR);

        return [
            'id' => (string) $row->id,
            'actor_id' => (string) $row->actor_id,
            'scope_type' => (string) $row->scope_type,
            'scope_id' => (string) $row->scope_id,
            'reserved_supplier_payment_id' => $row->reserved_supplier_payment_id === null
                ? null
                : (string) $row->reserved_supplier_payment_id,
            'idempotency_key' => (string) $row->idempotency_key,
            'request_hash' => (string) $row->request_hash,
            'status' => (string) $row->status,
            'locked_at' => $row->locked_at,
            'finalized_at' => $row->finalized_at,
            'expires_at' => $row->expires_at,
            'result_payload' => is_array($payload) ? $payload : null,
            'files' => $this->files((string) $row->id),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function files(string $uploadIntentId): array
    {
        return DB::table(self::FILES)
            ->where('upload_intent_id', $uploadIntentId)
            ->orderBy('ordinal')
            ->get()
            ->map(static fn (object $file): array => [
                'id' => (string) $file->id,
                'ordinal' => (int) $file->ordinal,
                'staging_path' => (string) $file->staging_path,
                'final_storage_path' => $file->final_storage_path === null
                    ? null
                    : (string) $file->final_storage_path,
                'original_filename' => (string) $file->original_filename,
                'declared_mime_type' => (string) $file->declared_mime_type,
                'declared_size_bytes' => (int) $file->declared_size_bytes,
                'verified_mime_type' => $file->verified_mime_type === null
                    ? null
                    : (string) $file->verified_mime_type,
                'verified_size_bytes' => $file->verified_size_bytes === null
                    ? null
                    : (int) $file->verified_size_bytes,
            ])
            ->values()
            ->all();
    }
}
