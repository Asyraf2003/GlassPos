<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

final class SupplierPaymentProofMimeTypeDetector
{
    /**
     * @var array<string, true>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => true,
        'image/jpeg' => true,
        'image/png' => true,
    ];

    public static function safe(string $path): string
    {
        $detectedMimeType = self::detect($path);

        return isset(self::ALLOWED_MIME_TYPES[$detectedMimeType])
            ? $detectedMimeType
            : 'application/octet-stream';
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
}
