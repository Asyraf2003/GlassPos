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
     *     effective_service_price:int,
     *     service_total:int,
     *     service_fee:int,
     *     service_store_profit:int
     * }
     */
    public function calculate(int $productPrice, int $servicePrice, ?int $packageTotal): array
    {
        $serviceTotal = max(0, $servicePrice);
        $resolvedPackageTotal = $packageTotal ?? ($productPrice + $serviceTotal);
        $serviceFee = intdiv($serviceTotal, 5);
        $storeProfit = $serviceTotal - $serviceFee;

        return [
            // Legacy compatibility fields stay neutral so additional product lines are never
            // mistaken for package margin. New presentation code uses the explicit service keys.
            'minimum_total' => $resolvedPackageTotal,
            'package_total' => $resolvedPackageTotal,
            'package_margin' => 0,
            'package_profit' => $storeProfit,
            'package_service_extra' => $serviceFee,
            'effective_service_price' => $serviceTotal,
            'service_total' => $serviceTotal,
            'service_fee' => $serviceFee,
            'service_store_profit' => $storeProfit,
        ];
    }
}
