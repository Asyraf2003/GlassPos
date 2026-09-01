<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\PaymentComponentAllocation;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\Exceptions\DomainException;
use PHPUnit\Framework\TestCase;

final class PaymentComponentTypeTest extends TestCase
{
    public function test_all_returns_expected_component_types(): void
    {
        $this->assertSame([
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            PaymentComponentType::SERVICE_FEE,
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART,
        ], PaymentComponentType::all());
    }

    public function test_assert_valid_rejects_unknown_component_type(): void
    {
        $this->expectException(DomainException::class);

        PaymentComponentType::assertValid('unknown_component');
    }
}
