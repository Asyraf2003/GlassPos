<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;

final class LaravelSupplierPaymentProofDirectUploadAdapter implements SupplierPaymentProofDirectUploadPort
{
    private readonly SupplierPaymentProofPresignedUploadFactory $uploads;

    public function __construct(
        ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {
        $this->uploads = new SupplierPaymentProofPresignedUploadFactory($failures);
    }

    public function prepareMany(string $uploadIntentId, array $files, int $expiresInSeconds = 900): array
    {
        $intentId = trim($uploadIntentId);

        if ($intentId === '' || $files === [] || count($files) > SupplierPaymentProofUploadLimits::MAX_FILES) {
            return [];
        }

        $normalized = [];

        foreach ($files as $file) {
            $candidate = $this->normalize($file);

            if (! $this->isValidFile($intentId, $candidate)) {
                return [];
            }

            $normalized[] = $candidate;
        }

        return $this->uploads->make($intentId, $normalized, max(60, min($expiresInSeconds, 3600)));
    }

    /**
     * @param  array<string,mixed>  $file
     * @return array{storage_path:string,original_filename:string,mime_type:string,file_size_bytes:int}
     */
    private function normalize(array $file): array
    {
        return [
            'storage_path' => trim((string) ($file['storage_path'] ?? '')),
            'original_filename' => trim((string) ($file['original_filename'] ?? '')),
            'mime_type' => trim((string) ($file['mime_type'] ?? '')),
            'file_size_bytes' => (int) ($file['file_size_bytes'] ?? 0),
        ];
    }

    /** @param array{storage_path:string,original_filename:string,mime_type:string,file_size_bytes:int} $file */
    private function isValidFile(string $intentId, array $file): bool
    {
        return $file['original_filename'] !== '' && strlen($file['original_filename']) <= 255
            && SupplierPaymentProofMimeTypes::normalizeAllowed($file['mime_type']) === strtolower($file['mime_type'])
            && $file['file_size_bytes'] > 0
            && $file['file_size_bytes'] <= SupplierPaymentProofUploadLimits::MAX_BYTES_PER_FILE
            && SupplierPaymentProofUploadStagingPathGuard::belongsToIntent($intentId, $file['storage_path']);
    }
}
