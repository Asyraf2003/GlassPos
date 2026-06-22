<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Payment\Services;

use App\Application\Payment\Services\RefundPairLimitGuard;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class RefundPairLimitGuardTest extends TestCase
{
    public function test_allows_refund_within_allocated_payment_note_pair(): void
    {
        RefundPairLimitGuard::assertWithinAllocated(
            Money::fromInt(100000),
            Money::fromInt(20000),
            Money::fromInt(30000),
        );

        $this->assertTrue(true);
    }

    public function test_allows_refund_that_exactly_reaches_allocated_pair_total(): void
    {
        RefundPairLimitGuard::assertWithinAllocated(
            Money::fromInt(100000),
            Money::fromInt(40000),
            Money::fromInt(60000),
        );

        $this->assertTrue(true);
    }

    public function test_rejects_refund_that_exceeds_allocated_pair_total(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Refund melebihi total allocation untuk payment-note pair.');

        RefundPairLimitGuard::assertWithinAllocated(
            Money::fromInt(100000),
            Money::fromInt(70000),
            Money::fromInt(40000),
        );
    }

    public function test_rejects_refund_when_pair_already_fully_refunded(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Refund melebihi total allocation untuk payment-note pair.');

        RefundPairLimitGuard::assertWithinAllocated(
            Money::fromInt(100000),
            Money::fromInt(100000),
            Money::fromInt(1),
        );
    }
}
