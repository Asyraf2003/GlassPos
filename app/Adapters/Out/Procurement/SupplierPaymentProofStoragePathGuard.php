<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

final class SupplierPaymentProofStoragePathGuard
{
    public const DIRECTORY_PREFIX = 'supplier-payment-proofs/';

    public static function directory(string $supplierPaymentId): string
    {
        return self::DIRECTORY_PREFIX . trim($supplierPaymentId);
    }

    public static function isValid(string $path): bool
    {
        $path = trim($path);

        if ($path === '') {
            return false;
        }

        if (
            str_contains($path, "\0")
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, '://')
            || str_starts_with($path, '/')
            || preg_match('/(?:^|\/)[A-Za-z]:\//', $path) === 1
        ) {
            return false;
        }

        return str_starts_with($path, self::DIRECTORY_PREFIX);
    }
}
