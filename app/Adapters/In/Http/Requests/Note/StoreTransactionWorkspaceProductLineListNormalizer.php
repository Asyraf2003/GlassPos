<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceProductLineListNormalizer
{
    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    public static function normalizeMany(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $lines = [];

        foreach (StoreTransactionWorkspaceProductLineShape::rawLines($value) as $line) {
            $normalized = StoreTransactionWorkspaceProductLineValueNormalizer::normalize($line);

            if (self::hasAnyValue($normalized)) {
                $lines[] = $normalized;
            }
        }

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyLine(): array
    {
        return [
            'product_id' => null,
            'qty' => null,
            'unit_price_rupiah' => null,
            'price_basis' => null,
        ];
    }

    /**
     * @param array<string, mixed> $line
     */
    private static function hasAnyValue(array $line): bool
    {
        foreach (['product_id', 'qty', 'unit_price_rupiah', 'price_basis'] as $key) {
            if (($line[$key] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }
}
