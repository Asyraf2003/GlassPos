<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class LaravelSupplierPaymentProofFileStorageAdapter implements SupplierPaymentProofFileStoragePort
{
    public function storeMany(string $supplierPaymentId, array $files): array
    {
        $storedFiles = [];
        $storedPaths = [];
        $disk = Storage::disk('local');

        try {
            foreach ($files as $file) {
                $sourcePath = trim((string) ($file['source_path'] ?? ''));

                if ($sourcePath === '' || ! is_file($sourcePath)) {
                    $this->deleteMany($storedPaths);

                    return [];
                }

                $storedPath = $disk->putFileAs(
                    $this->directory($supplierPaymentId),
                    new File($sourcePath),
                    $this->filename($file),
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
            Storage::disk('local')->delete($paths);
        }
    }

    public function exists(string $path): bool
    {
        $path = trim($path);

        return $path !== '' && Storage::disk('local')->exists($path);
    }

    public function get(string $path): ?string
    {
        try {
            $content = $this->exists($path) ? Storage::disk('local')->get($path) : null;
        } catch (Throwable) {
            return null;
        }

        return is_string($content) ? $content : null;
    }

    private function directory(string $supplierPaymentId): string
    {
        return 'supplier-payment-proofs/' . trim($supplierPaymentId);
    }

    private function filename(array $file): string
    {
        $extension = preg_replace(
            '/[^a-z0-9]/',
            '',
            strtolower((string) pathinfo((string) ($file['original_filename'] ?? ''), PATHINFO_EXTENSION)),
        ) ?? '';

        return bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    }
}
