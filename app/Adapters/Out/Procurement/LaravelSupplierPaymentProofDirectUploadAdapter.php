<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPreparation;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Throwable;

final class LaravelSupplierPaymentProofDirectUploadAdapter implements SupplierPaymentProofDirectUploadPort
{
    private readonly SupplierPaymentProofPresignedUploadFactory $uploads;

    public function __construct(
        private readonly ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {
        $this->uploads = new SupplierPaymentProofPresignedUploadFactory($failures);
    }

    public function prepareMany(
        string $uploadIntentId,
        array $files,
        int $expiresInSeconds = 900,
    ): SupplierPaymentProofDirectUploadPreparation {
        $intentId = trim($uploadIntentId);

        if ($intentId === '') {
            return $this->reject(SupplierPaymentProofFailureCode::INVALID_INTENT_ID, '', count($files), null);
        }

        if ($files === [] || count($files) > SupplierPaymentProofUploadLimits::MAX_FILES) {
            return $this->reject(SupplierPaymentProofFailureCode::INVALID_FILE_COUNT, $intentId, count($files), null);
        }

        $normalized = [];

        foreach (array_values($files) as $index => $file) {
            $candidate = $this->normalize($file);
            $failure = $this->validationFailure($intentId, $candidate);

            if ($failure !== null) {
                return $this->reject($failure, $intentId, count($files), $index + 1);
            }

            $normalized[] = $candidate;
        }

        return $this->uploads->make($intentId, $normalized, max(60, min($expiresInSeconds, 3600)));
    }

    /**
     * @param array<string,mixed> $file
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
    private function validationFailure(string $intentId, array $file): ?SupplierPaymentProofFailureCode
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

    private function reject(
        SupplierPaymentProofFailureCode $failureCode,
        string $intentId,
        int $fileCount,
        ?int $ordinal,
    ): SupplierPaymentProofDirectUploadPreparation {
        try {
            $this->failures?->report('prepare.adapter.validation', $failureCode, null, [
                'upload_intent_id' => $intentId,
                'file_count' => $fileCount,
                'file_ordinal' => $ordinal,
            ]);
        } catch (Throwable) {
        }

        return SupplierPaymentProofDirectUploadPreparation::failure($failureCode);
    }
}
