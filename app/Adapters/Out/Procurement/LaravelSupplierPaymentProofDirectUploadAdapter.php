<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class LaravelSupplierPaymentProofDirectUploadAdapter implements SupplierPaymentProofDirectUploadPort
{
    private const DISK = 'r2_private';

    public function prepareMany(string $uploadIntentId, array $files, int $expiresInSeconds = 900): array
    {
        $intentId = trim($uploadIntentId);

        if ($intentId === '' || $files === []) {
            return [];
        }

        $expiresInSeconds = max(60, min($expiresInSeconds, 3600));

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);
        $prepared = [];

        try {
            foreach ($files as $file) {
                $storagePath = trim((string) ($file['storage_path'] ?? ''));
                $originalFilename = trim((string) ($file['original_filename'] ?? ''));
                $mimeType = trim((string) ($file['mime_type'] ?? ''));
                $fileSizeBytes = (int) ($file['file_size_bytes'] ?? 0);

                if (! $this->isValidFile($intentId, $storagePath, $originalFilename, $mimeType, $fileSizeBytes)) {
                    return [];
                }

                $upload = $disk->temporaryUploadUrl(
                    $storagePath,
                    now()->addSeconds($expiresInSeconds),
                    ['ContentType' => $mimeType],
                );
                $url = trim((string) ($upload['url'] ?? ''));

                if ($url === '') {
                    return [];
                }

                $prepared[] = [
                    'storage_path' => $storagePath,
                    'original_filename' => $originalFilename,
                    'mime_type' => $mimeType,
                    'file_size_bytes' => $fileSizeBytes,
                    'upload_url' => $url,
                    'headers' => SupplierPaymentProofUploadHeaderNormalizer::forBrowser(
                        is_array($upload['headers'] ?? null) ? $upload['headers'] : [],
                    ),
                ];
            }
        } catch (Throwable) {
            return [];
        }

        return $prepared;
    }

    private function isValidFile(
        string $intentId,
        string $storagePath,
        string $originalFilename,
        string $mimeType,
        int $fileSizeBytes,
    ): bool {
        return $originalFilename !== ''
            && $mimeType !== ''
            && $fileSizeBytes > 0
            && SupplierPaymentProofUploadStagingPathGuard::belongsToIntent($intentId, $storagePath);
    }
}
