<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPreparation;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Filesystem\FilesystemAdapter;

final class SupplierPaymentProofPresignedUploadFactory
{
    private readonly SupplierPaymentProofPresignGateway $presigner;

    public function __construct(
        ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {
        $this->presigner = new SupplierPaymentProofPresignGateway($failures);
    }

    /** @param list<array<string,mixed>> $files */
    public function make(
        string $intentId,
        array $files,
        int $expiresInSeconds,
    ): SupplierPaymentProofDirectUploadPreparation {
        $disk = $this->presigner->resolveDisk($intentId, count($files));

        if (! $disk instanceof FilesystemAdapter) {
            return SupplierPaymentProofDirectUploadPreparation::failure(
                SupplierPaymentProofFailureCode::STORAGE_RESOLUTION_EXCEPTION,
            );
        }

        $prepared = [];

        foreach ($files as $index => $file) {
            $upload = $this->presigner->presign(
                $disk,
                $intentId,
                $file,
                count($files),
                $index + 1,
                $expiresInSeconds,
            );

            if (! is_array($upload)) {
                return SupplierPaymentProofDirectUploadPreparation::failure(
                    SupplierPaymentProofFailureCode::PRESIGN_EXCEPTION,
                );
            }

            $url = trim((string) ($upload['url'] ?? ''));

            if ($url === '') {
                $failure = SupplierPaymentProofFailureCode::EMPTY_PRESIGNED_URL;
                $this->presigner->report($failure, null, $disk, $intentId, count($files), $index + 1);

                return SupplierPaymentProofDirectUploadPreparation::failure($failure);
            }

            $prepared[] = $file + [
                'upload_url' => $url,
                'headers' => SupplierPaymentProofUploadHeaderNormalizer::forBrowser(
                    is_array($upload['headers'] ?? null) ? $upload['headers'] : [],
                ),
            ];
        }

        return SupplierPaymentProofDirectUploadPreparation::success($prepared);
    }
}
