<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SupplierPaymentProofPresignGateway
{
    public function __construct(
        private readonly ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {}

    public function resolveDisk(string $intentId, int $fileCount): ?FilesystemAdapter
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
    public function presign(
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

    public function report(
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
