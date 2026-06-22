<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\Policies;

use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class PaymentAllocationPolicyTest extends TestCase
{
    public function test_allows_allocation_that_matches_remaining_payment_and_note_outstanding(): void
    {
        $policy = new PaymentAllocationPolicy();

        $policy->assertAllocatable(
            Money::fromInt(40000),
            Money::fromInt(40000),
            Money::zero(),
            Money::fromInt(100000),
            Money::fromInt(60000),
        );

        $this->assertTrue(true);
    }

    public function test_allows_replacement_payment_when_caller_passes_net_allocated_after_refund(): void
    {
        $policy = new PaymentAllocationPolicy();

        $policy->assertAllocatable(
            Money::fromInt(40000),
            Money::fromInt(40000),
            Money::zero(),
            Money::fromInt(100000),
            Money::fromInt(60000),
        );

        $this->assertTrue(true);
    }

    public function test_rejects_zero_allocation_amount(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Amount alokasi payment harus lebih besar dari nol.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::zero(),
            Money::fromInt(100000),
            Money::zero(),
            Money::fromInt(100000),
            Money::zero(),
        );
    }

    public function test_rejects_zero_payment_amount(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Amount customer payment harus lebih besar dari nol.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::fromInt(10000),
            Money::zero(),
            Money::zero(),
            Money::fromInt(100000),
            Money::zero(),
        );
    }

    public function test_rejects_allocation_that_exceeds_remaining_payment(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Amount alokasi payment melebihi sisa payment yang tersedia.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::fromInt(60000),
            Money::fromInt(100000),
            Money::fromInt(50000),
            Money::fromInt(200000),
            Money::zero(),
        );
    }

    public function test_rejects_allocation_that_exceeds_note_outstanding(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Amount alokasi payment melebihi outstanding note.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::fromInt(50000),
            Money::fromInt(50000),
            Money::zero(),
            Money::fromInt(100000),
            Money::fromInt(60000),
        );
    }

    public function test_rejects_when_existing_note_allocation_already_exceeds_note_total(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Total alokasi pada note melebihi total note.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::fromInt(10000),
            Money::fromInt(10000),
            Money::zero(),
            Money::fromInt(100000),
            Money::fromInt(110000),
        );
    }

    public function test_rejects_when_existing_payment_allocation_already_exceeds_payment_amount(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Total alokasi pada customer payment melebihi amount payment.');

        (new PaymentAllocationPolicy())->assertAllocatable(
            Money::fromInt(10000),
            Money::fromInt(100000),
            Money::fromInt(110000),
            Money::fromInt(200000),
            Money::zero(),
        );
    }
}
