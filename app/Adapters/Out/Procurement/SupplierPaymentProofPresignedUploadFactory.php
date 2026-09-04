<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPreparation;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SupplierPaymentProofPresignedUploadFactory
{
    public function __construct(
        private readonly ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {}

    /** @param list<array<string,mixed>> $files */
    public function make(
        string $intentId,
        array $files,
        int $expiresInSeconds,
    ): SupplierPaymentProofDirectUploadPreparation {
        $disk = $this->resolveDisk($intentId, count($files));

        if (! $disk instanceof FilesystemAdapter) {
            return SupplierPaymentProofDirectUploadPreparation::failure(
                SupplierPaymentProofFailureCode::STORAGE_RESOLUTION_EXCEPTION,
            );
        }

        $prepared = [];

        foreach ($files as $index => $file) {
            $upload = $this->presign($disk, $intentId, $file, count($files), $index + 1, $expiresInSeconds);

            if (! is_array($upload)) {
                return SupplierPaymentProofDirectUploadPreparation::failure(
                    SupplierPaymentProofFailureCode::PRESIGN_EXCEPTION,
                );
            }

            $url = trim((string) ($upload['url'] ?? ''));

            if ($url === '') {
                $failure = SupplierPaymentProofFailureCode::EMPTY_PRESIGNED_URL;
                $this->report($failure, null, $disk, $intentId, count($files), $index + 1);

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

    private function resolveDisk(string $intentId, int $fileCount): ?FilesystemAdapter
    {
        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('r2_private');

            return $disk;
        } catch (Throwable $exception) {
            $this->report(
                SupplierPaymentProofFailureCode::STORAGE_RESOLUTION_EXCEPTION,
                $exception,
                null,
                $intentId,
                $fileCount,
                1,
            );

            return null;
        }
    }

    /** @param array<string,mixed> $file @return array<string,mixed>|null */
    private function presign(
        FilesystemAdapter $disk,
        string $intentId,
        array $file,
        int $fileCount,
        int $ordinal,
        int $expiresInSeconds,
    ): ?array {
        try {
            return $disk->temporaryUploadUrl(
                (string) $file['storage_path'],
                now()->addSeconds($expiresInSeconds),
                ['ContentType' => (string) $file['mime_type']],
            );
        } catch (Throwable $exception) {
            $this->report(
                SupplierPaymentProofFailureCode::PRESIGN_EXCEPTION,
                $exception,
                $disk,
                $intentId,
                $fileCount,
                $ordinal,
            );

            return null;
        }
    }

    private function report(
        SupplierPaymentProofFailureCode $failureCode,
        ?Throwable $exception,
        ?FilesystemAdapter $disk,
        string $intentId,
        int $fileCount,
        int $ordinal,
    ): void {
        try {
            $this->failures?->report(
                'prepare.presign',
                $failureCode,
                $exception,
                SupplierPaymentProofPresignRuntimeContext::capture($disk, $intentId, $fileCount, $ordinal),
            );
        } catch (Throwable) {
        }
    }
}
