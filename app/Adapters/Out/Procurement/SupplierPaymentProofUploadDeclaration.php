<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;

final class SupplierPaymentProofUploadDeclaration
{
    /**
     * @param  array<string,mixed>  $file
     * @return array{storage_path:string,original_filename:string,mime_type:string,file_size_bytes:int}
     */
    public static function normalize(array $file): array
    {
        return [
            'storage_path' => trim((string) ($file['storage_path'] ?? '')),
            'original_filename' => trim((string) ($file['original_filename'] ?? '')),
            'mime_type' => trim((string) ($file['mime_type'] ?? '')),
            'file_size_bytes' => (int) ($file['file_size_bytes'] ?? 0),
        ];
    }

    /** @param array{storage_path:string,original_filename:string,mime_type:string,file_size_bytes:int} $file */
    public static function validationFailure(string $intentId, array $file): ?SupplierPaymentProofFailureCode
    {
        if ($file['original_filename'] === '' || strlen($file['original_filename']) > 255) {
            return SupplierPaymentProofFailureCode::INVALID_ORIGINAL_FILENAME;
        }

        if (SupplierPaymentProofMimeTypes::normalizeAllowed($file['mime_type']) !== strtolower($file['mime_type'])) {
            return SupplierPaymentProofFailureCode::INVALID_DECLARED_MIME;
        }

        if ($file['file_size_bytes'] < 1 || $file['file_size_bytes'] > SupplierPaymentProofUploadLimits::MAX_BYTES_PER_FILE) {
            return SupplierPaymentProofFailureCode::INVALID_DECLARED_SIZE;
        }

        return SupplierPaymentProofUploadStagingPathGuard::belongsToIntent($intentId, $file['storage_path'])
            ? null
            : SupplierPaymentProofFailureCode::INVALID_STAGING_PATH;
    }
}
