<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceProductLineValueNormalizer
{
    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    public static function normalize(array $line): array
    {
        return [
            'product_id' => self::trimOrNull($line['product_id'] ?? null),
            'qty' => self::integerOrNull($line['qty'] ?? null),
            'unit_price_rupiah' => self::integerOrNull($line['unit_price_rupiah'] ?? null),
            'price_basis' => self::trimOrNull($line['price_basis'] ?? null),
        ];
    }

    private static function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function integerOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $value);

        return is_string($cleaned) && $cleaned !== '' ? (int) $cleaned : null;
    }
}
