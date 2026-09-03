<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofObjectStoragePort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class LaravelSupplierPaymentProofObjectStorageAdapter implements SupplierPaymentProofObjectStoragePort
{
    private const DISK = 'r2_private';

    public function __construct(private readonly SupplierPaymentProofStagingObjectVerifier $verifier) {}

    public function verifyStaging(string $uploadIntentId, array $intentFile): ?array
    {
        return $this->verifier->verify($this->disk(), trim($uploadIntentId), $intentFile);
    }

    public function promote(string $uploadIntentId, string $supplierPaymentId, array $verifiedFile): ?array
    {
        $source = trim((string) ($verifiedFile['staging_path'] ?? ''));
        $size = (int) ($verifiedFile['verified_size_bytes'] ?? 0);
        $target = SupplierPaymentProofFinalPathFactory::make(
            $supplierPaymentId,
            (string) ($verifiedFile['id'] ?? ''),
            (string) ($verifiedFile['verified_mime_type'] ?? ''),
        );

        if (! SupplierPaymentProofUploadStagingPathGuard::belongsToIntent($uploadIntentId, $source)
            || ! SupplierPaymentProofStoragePathGuard::isValid($target) || $size < 1) {
            return null;
        }

        $disk = $this->disk();

        try {
            if (! $disk->copy($source, $target)
                || ! $disk->exists($target)
                || $disk->size($target) !== $size
                || ! $disk->delete($source)) {
                $disk->delete($target);

                return null;
            }

            return [
                'storage_path' => $target,
                'original_filename' => trim((string) ($verifiedFile['original_filename'] ?? '')),
                'mime_type' => (string) $verifiedFile['verified_mime_type'],
                'file_size_bytes' => $size,
                'intent_file_id' => (string) ($verifiedFile['id'] ?? ''),
            ];
        } catch (Throwable) {
            $disk->delete($target);

            return null;
        }
    }

    public function deleteMany(array $paths): bool
    {
        try {
            return $paths === [] || $this->disk()->delete($paths);
        } catch (Throwable) {
            return false;
        }
    }

    public function cleanupIntent(array $intent): bool
    {
        try {
            $disk = $this->disk();
            $existing = array_values(array_filter(
                SupplierPaymentProofIntentObjectPaths::all($intent),
                static fn (string $path): bool => $disk->exists($path),
            ));

            return $this->deleteMany($existing);
        } catch (Throwable) {
            return false;
        }
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);

        return $disk;
    }
}
