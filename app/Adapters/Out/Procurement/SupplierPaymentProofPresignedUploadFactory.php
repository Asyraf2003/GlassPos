<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class SupplierPaymentProofPresignedUploadFactory
{
    public function __construct(
        private readonly ?SupplierPaymentProofFailureReporterPort $failures = null,
    ) {}

    /** @param list<array<string,mixed>> $files @return list<array<string,mixed>> */
    public function make(string $intentId, array $files, int $expiresInSeconds): array
    {
        $disk = null;
        $prepared = [];

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('r2_private');

            foreach ($files as $index => $file) {
                $upload = $disk->temporaryUploadUrl(
                    (string) $file['storage_path'],
                    now()->addSeconds($expiresInSeconds),
                    ['ContentType' => (string) $file['mime_type']],
                );
                $url = trim((string) ($upload['url'] ?? ''));

                if ($url === '') {
                    throw new RuntimeException('Private object storage returned an empty presigned upload URL.');
                }

                $prepared[] = $file + [
                    'upload_url' => $url,
                    'headers' => SupplierPaymentProofUploadHeaderNormalizer::forBrowser(
                        is_array($upload['headers'] ?? null) ? $upload['headers'] : [],
                    ),
                ];
            }
        } catch (Throwable $exception) {
            $this->report($exception, $disk, $intentId, count($files), count($prepared) + 1);

            return [];
        }

        return $prepared;
    }

    private function report(
        Throwable $exception,
        ?FilesystemAdapter $disk,
        string $intentId,
        int $fileCount,
        int $ordinal,
    ): void {
        try {
            $this->failures?->report(
                'prepare.presign.exception',
                $exception,
                SupplierPaymentProofPresignRuntimeContext::capture($disk, $intentId, $fileCount, $ordinal),
            );
        } catch (Throwable) {
        }
    }
}
