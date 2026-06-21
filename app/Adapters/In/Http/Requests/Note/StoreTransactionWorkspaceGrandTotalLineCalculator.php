<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceGrandTotalLineCalculator
{
    public static function productLineTotal(mixed $lines): int
    {
        $total = 0;

        foreach (self::rawProductLines($lines) as $line) {
            $total += self::intValue($line['qty'] ?? null)
                * self::intValue($line['unit_price_rupiah'] ?? null);
        }

        return $total;
    }

	    public static function externalLineTotal(mixed $lines): int
	    {
	        $line = self::firstLine($lines);
	        $total = self::intValue($line['total_rupiah'] ?? null);

	        return $total > 0
	            ? $total
	            : self::intValue($line['qty'] ?? null) * self::intValue($line['unit_cost_rupiah'] ?? null);
	    }

    public static function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rawProductLines(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rawLines = self::looksLikeProductLine($value) ? [$value] : array_values($value);
        $lines = [];

        foreach ($rawLines as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param array<mixed> $value
     */
    private static function looksLikeProductLine(array $value): bool
    {
        return array_key_exists('product_id', $value)
            || array_key_exists('qty', $value)
            || array_key_exists('unit_price_rupiah', $value)
            || array_key_exists('price_basis', $value);
    }

    /**
     * @return array<string, mixed>
     */
    private static function firstLine(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first) ? $first : [];
    }
}
