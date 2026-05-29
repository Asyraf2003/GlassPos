<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceDuplicateProductLineGuard
{
    private const MESSAGE = 'Produk yang sama tidak boleh diinput dua kali dalam satu baris servis. Aturan ini mencegah alokasi paket dan stok tercatat ganda. Naikkan qty pada baris produk yang sudah ada.';

    /**
     * @param list<array<string, mixed>> $lines
     */
    public static function assertUnique(array $lines): void
    {
        $seen = [];

        foreach ($lines as $line) {
            $productId = self::productId($line);

            if ($productId === null) {
                continue;
            }

            if (isset($seen[$productId])) {
                throw new DomainException(self::MESSAGE);
            }

            $seen[$productId] = true;
        }
    }

    /**
     * @param array<string, mixed> $line
     */
    private static function productId(array $line): ?string
    {
        $value = $line['product_id'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
