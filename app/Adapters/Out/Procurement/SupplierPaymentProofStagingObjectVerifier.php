<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;
use Illuminate\Filesystem\FilesystemAdapter;
use Throwable;

final class SupplierPaymentProofStagingObjectVerifier
{
    /** @param array<string,mixed> $file @return array<string,mixed>|null */
    public function verify(FilesystemAdapter $disk, string $intentId, array $file): ?array
    {
        $path = trim((string) ($file['staging_path'] ?? ''));
        $declaredSize = (int) ($file['declared_size_bytes'] ?? 0);

        if (! SupplierPaymentProofUploadStagingPathGuard::belongsToIntent($intentId, $path)) {
            return null;
        }

        $temporaryPath = null;

        try {
            if (! $disk->exists($path)) {
                return null;
            }

            $actualSize = $disk->size($path);

            if ($actualSize < 1 || $actualSize > SupplierPaymentProofUploadLimits::MAX_BYTES_PER_FILE
                || $actualSize !== $declaredSize) {
                return null;
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'glasspos-proof-verify-');

            if (! is_string($temporaryPath) || ! $this->copyBounded($disk, $path, $temporaryPath, $actualSize)) {
                return null;
            }

            $mimeType = SupplierPaymentProofMimeTypeDetector::safe($temporaryPath);

            if ($mimeType === 'application/octet-stream') {
                return null;
            }

            return array_merge($file, [
                'verified_mime_type' => $mimeType,
                'verified_size_bytes' => $actualSize,
            ]);
        } catch (Throwable) {
            return null;
        } finally {
            if (is_string($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function copyBounded(FilesystemAdapter $disk, string $path, string $target, int $size): bool
    {
        $source = $disk->readStream($path);
        $destination = fopen($target, 'wb');

        if (! is_resource($source) || ! is_resource($destination)) {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($destination)) {
                fclose($destination);
            }

            return false;
        }

        try {
            $copied = stream_copy_to_stream(
                $source,
                $destination,
                SupplierPaymentProofUploadLimits::MAX_BYTES_PER_FILE + 1,
            );

            return is_int($copied) && $copied === $size;
        } finally {
            fclose($source);
            fclose($destination);
        }
    }
}
