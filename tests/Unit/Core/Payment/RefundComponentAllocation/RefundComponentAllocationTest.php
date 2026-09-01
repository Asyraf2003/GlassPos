<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\RefundComponentAllocation;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Payment\RefundComponentAllocation\RefundComponentAllocation;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class RefundComponentAllocationTest extends TestCase
{
    public function test_can_create_valid_refund_component_allocation(): void
    {
        $allocation = RefundComponentAllocation::create(
            'refund-alloc-1',
            'customer-refund-1',
            'customer-payment-1',
            'note-1',
            'work-item-1',
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            'work-item-1',
            Money::fromInt(2000),
            1,
        );

        $this->assertSame('refund-alloc-1', $allocation->id());
        $this->assertSame('customer-refund-1', $allocation->customerRefundId());
        $this->assertSame('customer-payment-1', $allocation->customerPaymentId());
        $this->assertSame(2000, $allocation->refundedAmountRupiah()->amount());
        $this->assertSame(1, $allocation->refundPriority());
    }

    public function test_rejects_zero_refunded_amount(): void
    {
        $this->expectException(DomainException::class);

        RefundComponentAllocation::create(
            'refund-alloc-1',
            'customer-refund-1',
            'customer-payment-1',
            'note-1',
            'work-item-1',
            PaymentComponentType::SERVICE_FEE,
            'work-item-1',
            Money::zero(),
            1,
        );
    }
}
