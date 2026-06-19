<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

final class ServiceProductTemplatePackageSplitCalculator
{
    /**
     * @return array{
     *     minimum_total:int,
     *     package_total:int,
     *     package_margin:int,
     *     package_profit:int,
     *     package_service_extra:int,
     *     effective_service_price:int
     * }
     */
    public function calculate(int $productPrice, int $servicePrice, ?int $packageTotal): array
    {
        $minimumTotal = $productPrice + $servicePrice;
        $resolvedPackageTotal = $packageTotal ?? $minimumTotal;
        $packageMargin = max(0, $resolvedPackageTotal - $minimumTotal);
        $packageServiceExtra = intdiv($packageMargin, 5);
        $packageProfit = $packageMargin - $packageServiceExtra;

        return [
            'minimum_total' => $minimumTotal,
            'package_total' => $resolvedPackageTotal,
            'package_margin' => $packageMargin,
            'package_profit' => $packageProfit,
            'package_service_extra' => $packageServiceExtra,
            'effective_service_price' => $servicePrice + $packageServiceExtra,
        ];
    }
}
