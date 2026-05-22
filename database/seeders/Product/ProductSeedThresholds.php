<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

final class ProductSeedThresholds
{
    /**
     * @return array{reorderPointQty:int,criticalThresholdQty:int}
     */
    public static function forIndex(int $index): array
    {
        return match ($index % 3) {
            0 => ['reorderPointQty' => 5, 'criticalThresholdQty' => 2],
            1 => ['reorderPointQty' => 8, 'criticalThresholdQty' => 3],
            default => ['reorderPointQty' => 12, 'criticalThresholdQty' => 5],
        };
    }
}
