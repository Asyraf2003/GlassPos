<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class LaravelSupplierPaymentProofFileStorageAdapter implements SupplierPaymentProofFileStoragePort
{
    public const DIRECTORY_PREFIX = SupplierPaymentProofStoragePathGuard::DIRECTORY_PREFIX;

    private const DISK = 'r2_private';

    public function storeMany(string $supplierPaymentId, array $files): array
    {
        $storedFiles = [];
        $storedPaths = [];
        $disk = Storage::disk(self::DISK);

        try {
            foreach ($files as $file) {
                $sourcePath = trim((string) ($file['source_path'] ?? ''));

                if ($sourcePath === '' || ! is_file($sourcePath)) {
                    $this->deleteMany($storedPaths);

                    return [];
                }

                $storedPath = $disk->putFileAs(
                    SupplierPaymentProofStoragePathGuard::directory($supplierPaymentId),
                    new File($sourcePath),
                    SupplierPaymentProofStoredFilenameFactory::make(
                        (string) ($file['original_filename'] ?? '')
                    ),
                );

                if (! is_string($storedPath) || $storedPath === '') {
                    $this->deleteMany($storedPaths);

                    return [];
                }

                $storedPaths[] = $storedPath;
                $storedFiles[] = [
                    'storage_path' => $storedPath,
                    'original_filename' => trim((string) ($file['original_filename'] ?? '')),
                    'mime_type' => SupplierPaymentProofMimeTypeDetector::safe($sourcePath),
                    'file_size_bytes' => (int) ($file['file_size_bytes'] ?? 0),
                ];
            }
        } catch (Throwable) {
            $this->deleteMany($storedPaths);

            return [];
        }

        return $storedFiles;
    }

    public function deleteMany(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    public function exists(string $path): bool
    {
        $path = trim($path);

        return self::isValidPath($path) && Storage::disk(self::DISK)->exists($path);
    }

    public function get(string $path): ?string
    {
        try {
            $content = $this->exists($path) ? Storage::disk(self::DISK)->get($path) : null;
        } catch (Throwable) {
            return null;
        }

        return is_string($content) ? $content : null;
    }

    public static function isValidPath(string $path): bool
    {
        return SupplierPaymentProofStoragePathGuard::isValid($path);
    }
}
