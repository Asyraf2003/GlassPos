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

    public function prepareMany(string $supplierPaymentId, array $files, int $expiresInSeconds = 900): array
    {
        $paymentId = trim($supplierPaymentId);

        if ($paymentId === '' || $files === []) {
            return [];
        }

        $expiresInSeconds = max(60, min($expiresInSeconds, 3600));

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);
        $prepared = [];

        try {
            foreach ($files as $file) {
                $originalFilename = trim((string) ($file['original_filename'] ?? ''));
                $mimeType = trim((string) ($file['mime_type'] ?? ''));
                $fileSizeBytes = (int) ($file['file_size_bytes'] ?? 0);

                if ($originalFilename === '' || $mimeType === '' || $fileSizeBytes < 1) {
                    return [];
                }

                $storagePath = SupplierPaymentProofStoragePathGuard::directory($paymentId)
                    .'/'.SupplierPaymentProofStoredFilenameFactory::make($originalFilename);

                if (! SupplierPaymentProofStoragePathGuard::isValid($storagePath)) {
                    return [];
                }

                $upload = $disk->temporaryUploadUrl(
                    $storagePath,
                    now()->addSeconds($expiresInSeconds),
                    ['ContentType' => $mimeType],
                );

                $url = trim((string) ($upload['url'] ?? ''));
                $headers = is_array($upload['headers'] ?? null) ? $upload['headers'] : [];

                if ($url === '') {
                    return [];
                }

                $prepared[] = [
                    'storage_path' => $storagePath,
                    'original_filename' => $originalFilename,
                    'mime_type' => $mimeType,
                    'file_size_bytes' => $fileSizeBytes,
                    'upload_url' => $url,
                    'headers' => SupplierPaymentProofUploadHeaderNormalizer::forBrowser($headers),
                ];
            }
        } catch (Throwable) {
            return [];
        }

        return $prepared;
    }
}
