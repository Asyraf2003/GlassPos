<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\PaymentComponentAllocation;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class PaymentComponentAllocationTest extends TestCase
{
    public function test_can_create_valid_payment_component_allocation(): void
    {
        $allocation = PaymentComponentAllocation::create(
            'alloc-1',
            'payment-1',
            'note-1',
            'work-item-1',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'store-stock-line-1',
            Money::fromInt(5000),
            Money::fromInt(3000),
            1,
        );

        $this->assertSame('alloc-1', $allocation->id());
        $this->assertSame('payment-1', $allocation->customerPaymentId());
        $this->assertSame('note-1', $allocation->noteId());
        $this->assertSame('work-item-1', $allocation->workItemId());
        $this->assertSame('store-stock-line-1', $allocation->componentRefId());
        $this->assertSame(5000, $allocation->componentAmountRupiahSnapshot()->amount());
        $this->assertSame(3000, $allocation->allocatedAmountRupiah()->amount());
        $this->assertSame(1, $allocation->allocationPriority());
    }

    public function test_rejects_allocated_amount_larger_than_snapshot(): void
    {
        $this->expectException(DomainException::class);

        PaymentComponentAllocation::create(
            'alloc-1',
            'payment-1',
            'note-1',
            'work-item-1',
            PaymentComponentType::SERVICE_FEE,
            'work-item-1',
            Money::fromInt(5000),
            Money::fromInt(6000),
            1,
        );
    }
}
