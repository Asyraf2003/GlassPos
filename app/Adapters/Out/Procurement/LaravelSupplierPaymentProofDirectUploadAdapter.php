<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

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
            $candidate = SupplierPaymentProofUploadDeclaration::normalize($file);
            $failure = SupplierPaymentProofUploadDeclaration::validationFailure($intentId, $candidate);

            if ($failure !== null) {
                return $this->reject($failure, $intentId, count($files), $index + 1);
            }

            $normalized[] = $candidate;
        }

        return $this->uploads->make($intentId, $normalized, max(60, min($expiresInSeconds, 3600)));
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
