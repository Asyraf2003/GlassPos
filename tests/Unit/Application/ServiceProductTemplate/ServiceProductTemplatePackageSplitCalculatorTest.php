<?php

declare(strict_types=1);

namespace Tests\Unit\Application\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\Services\ServiceProductTemplatePackageSplitCalculator;
use PHPUnit\Framework\TestCase;

final class ServiceProductTemplatePackageSplitCalculatorTest extends TestCase
{
    public function test_service_value_is_split_into_twenty_percent_fee_and_eighty_percent_store_profit(): void
    {
        $result = (new ServiceProductTemplatePackageSplitCalculator())->calculate(
            productPrice: 115_000,
            servicePrice: 50_000,
            packageTotal: 215_000,
        );

        self::assertSame(215_000, $result['package_total']);
        self::assertSame(50_000, $result['service_total']);
        self::assertSame(10_000, $result['service_fee']);
        self::assertSame(40_000, $result['service_store_profit']);
        self::assertSame(40_000, $result['package_profit']);
        self::assertSame(0, $result['package_margin']);
    }

    public function test_additional_product_value_is_not_treated_as_service_margin(): void
    {
        $result = (new ServiceProductTemplatePackageSplitCalculator())->calculate(
            productPrice: 60_000,
            servicePrice: 40_000,
            packageTotal: 150_000,
        );

        self::assertSame(8_000, $result['service_fee']);
        self::assertSame(32_000, $result['service_store_profit']);
        self::assertSame(0, $result['package_margin']);
    }
}
