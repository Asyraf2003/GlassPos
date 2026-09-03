<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierPaymentProof;

final class SupplierPaymentProofMimeTypes
{
    private const EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    public static function normalizeAllowed(string $mimeType): ?string
    {
        $normalized = strtolower(trim($mimeType));
        $normalized = match ($normalized) {
            'image/heic-sequence' => 'image/heic',
            'image/heif-sequence' => 'image/heif',
            default => $normalized,
        };

        return isset(self::EXTENSIONS[$normalized]) ? $normalized : null;
    }

    public static function extension(string $mimeType): ?string
    {
        $normalized = self::normalizeAllowed($mimeType);

        return $normalized === null ? null : self::EXTENSIONS[$normalized];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::EXTENSIONS);
    }
}
