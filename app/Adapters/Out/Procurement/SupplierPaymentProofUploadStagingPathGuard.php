<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

final class SupplierPaymentProofUploadStagingPathGuard
{
    public const DIRECTORY_PREFIX = 'supplier-payment-proof-uploads';

    public static function directory(string $uploadIntentId): string
    {
        $intentId = trim($uploadIntentId);

        if (! self::isSafeSegment($intentId)) {
            return '';
        }

        return self::DIRECTORY_PREFIX.'/'.$intentId;
    }

    public static function belongsToIntent(string $uploadIntentId, string $path): bool
    {
        $directory = self::directory($uploadIntentId);
        $path = trim($path);

        if ($directory === '' || $path === '' || str_contains($path, '..')) {
            return false;
        }

        if (! str_starts_with($path, $directory.'/')) {
            return false;
        }

        $relative = substr($path, strlen($directory) + 1);

        return $relative !== ''
            && ! str_contains($relative, '/')
            && self::isSafeSegment($relative);
    }

    private static function isSafeSegment(string $value): bool
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1;
    }
}
