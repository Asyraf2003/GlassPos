<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;

final class SupplierPaymentProofDirectUploadRequestValidator
{
    /** @param list<array<string,mixed>> $files */
    public function validate(
        string $scopeType,
        string $scopeId,
        array $files,
        string $actorId,
        string $idempotencyKey,
    ): Result {
        $scopeType = strtolower(trim($scopeType));
        $scopeId = trim($scopeId);
        $actorId = trim($actorId);
        $idempotencyKey = trim($idempotencyKey);

        if ($actorId === '' || strlen($actorId) > 100) {
            return $this->fail('Actor bukti pembayaran supplier wajib ada.', 'actor', 'ACTOR_REQUIRED');
        }

        if (! in_array($scopeType, ['supplier_payment', 'supplier_invoice'], true)
            || $scopeId === '' || strlen($scopeId) > 100) {
            return $this->fail('Scope bukti pembayaran supplier tidak valid.', 'scope', 'INVALID_UPLOAD_SCOPE');
        }

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            return $this->fail('Kunci idempotensi upload tidak valid.', 'idempotency_key', 'INVALID_IDEMPOTENCY_KEY');
        }

        if (count($files) < 1 || count($files) > SupplierPaymentProofUploadLimits::MAX_FILES) {
            return $this->fail('Jumlah bukti pembayaran harus 1 sampai 3 file.', 'files', 'INVALID_FILE_COUNT');
        }

        $normalized = [];

        foreach ($files as $file) {
            $name = trim((string) ($file['original_filename'] ?? ''));
            $mime = SupplierPaymentProofMimeTypes::normalizeAllowed((string) ($file['mime_type'] ?? ''));
            $size = (int) ($file['file_size_bytes'] ?? 0);

            if ($name === '' || strlen($name) > 255 || $mime === null
                || $size < 1 || $size > SupplierPaymentProofUploadLimits::MAX_BYTES_PER_FILE) {
                return $this->fail('Deklarasi bukti pembayaran tidak valid.', 'files', 'INVALID_FILE_DECLARATION');
            }

            $normalized[] = [
                'original_filename' => $name,
                'mime_type' => $mime,
                'file_size_bytes' => $size,
            ];
        }

        $canonical = ['scope_type' => $scopeType, 'scope_id' => $scopeId, 'files' => $normalized];

        return Result::success($canonical + [
            'actor_id' => $actorId,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR)),
        ]);
    }

    private function fail(string $message, string $field, string $code): Result
    {
        return Result::failure($message, [$field => [$code]]);
    }
}
