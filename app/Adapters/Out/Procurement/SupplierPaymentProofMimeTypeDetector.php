<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;

final class SupplierPaymentProofMimeTypeDetector
{
    public static function safe(string $path): string
    {
        $detectedMimeType = SupplierPaymentProofMimeTypes::normalizeAllowed(self::detect($path));

        return $detectedMimeType ?? self::detectIsoBaseMediaType($path) ?? 'application/octet-stream';
    }

    private static function detect(string $path): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $fileInfo->file($path);

        if (! is_string($detectedMimeType)) {
            return 'application/octet-stream';
        }

        return strtolower(trim($detectedMimeType));
    }

    private static function detectIsoBaseMediaType(string $path): ?string
    {
        $header = file_get_contents($path, false, null, 0, 64);

        if (! is_string($header) || strlen($header) < 12 || substr($header, 4, 4) !== 'ftyp') {
            return null;
        }

        $brands = substr($header, 8);

        if (str_contains($brands, 'avif') || str_contains($brands, 'avis')) {
            return null;
        }

        foreach (['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis'] as $brand) {
            if (str_contains($brands, $brand)) {
                return 'image/heic';
            }
        }

        return str_contains($brands, 'mif1') || str_contains($brands, 'msf1')
            ? 'image/heif'
            : null;
    }
}
