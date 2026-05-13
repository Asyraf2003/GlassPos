<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Application\Note\Services\BuildCreateNoteRevisionSettlement;
use App\Application\Note\Services\BuildNoteRevisionSettlement;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BuildCreateNoteRevisionSettlementTest extends TestCase
{
    public function test_it_uses_component_payment_and_refund_totals_when_component_settlement_exists(): void
    {
        $settlement = $this->builder(
            componentPaid: 300000,
            componentRefunded: 100000,
            legacyPaid: 999000,
            legacyRefunded: 888000,
        )->build('set-1', 'rev-1', 'note-1', 200000, $this->time());

        $this->assertSame(NoteRevisionSettlement::STATUS_PAID, $settlement->settlementStatus);
        $this->assertSame(300000, $settlement->carryForwardPaidRupiah);
        $this->assertSame(100000, $settlement->carryForwardRefundedRupiah);
        $this->assertSame(200000, $settlement->netPaidRupiah);
        $this->assertSame(0, $settlement->outstandingRupiah);
        $this->assertSame(0, $settlement->surplusRupiah);
    }

    public function test_it_falls_back_to_legacy_payment_and_refund_totals_without_component_settlement(): void
    {
        $settlement = $this->builder(
            componentPaid: 0,
            componentRefunded: 0,
            legacyPaid: 300000,
            legacyRefunded: 100000,
        )->build('set-1', 'rev-1', 'note-1', 250000, $this->time());

        $this->assertSame(NoteRevisionSettlement::STATUS_UNDERPAID, $settlement->settlementStatus);
        $this->assertSame(300000, $settlement->carryForwardPaidRupiah);
        $this->assertSame(100000, $settlement->carryForwardRefundedRupiah);
        $this->assertSame(200000, $settlement->netPaidRupiah);
        $this->assertSame(50000, $settlement->outstandingRupiah);
        $this->assertSame(0, $settlement->surplusRupiah);
    }

    public function test_it_marks_component_surplus_as_overpaid_pending(): void
    {
        $settlement = $this->builder(
            componentPaid: 300000,
            componentRefunded: 50000,
            legacyPaid: 0,
            legacyRefunded: 0,
        )->build('set-1', 'rev-1', 'note-1', 200000, $this->time());

        $this->assertSame(NoteRevisionSettlement::STATUS_OVERPAID_PENDING, $settlement->settlementStatus);
        $this->assertSame(250000, $settlement->netPaidRupiah);
        $this->assertSame(0, $settlement->outstandingRupiah);
        $this->assertSame(50000, $settlement->surplusRupiah);
    }

    private function builder(
        int $componentPaid,
        int $componentRefunded,
        int $legacyPaid,
        int $legacyRefunded,
    ): BuildCreateNoteRevisionSettlement {
        return new BuildCreateNoteRevisionSettlement(
            $this->componentPayments($componentPaid),
            $this->legacyPayments($legacyPaid),
            $this->componentRefunds($componentRefunded),
            $this->legacyRefunds($legacyRefunded),
            new BuildNoteRevisionSettlement(),
        );
    }

    private function componentPayments(int $amount): PaymentComponentAllocationReaderPort
    {
        return new class($amount) implements PaymentComponentAllocationReaderPort {
            public function __construct(private readonly int $amount) {}
            public function getTotalAllocatedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
            public function getTotalAllocatedAmountByWorkItemId(string $workItemId): Money { return Money::zero(); }
            public function listByNoteId(string $noteId): array { return []; }
        };
    }

    private function componentRefunds(int $amount): RefundComponentAllocationReaderPort
    {
        return new class($amount) implements RefundComponentAllocationReaderPort {
            public function __construct(private readonly int $amount) {}
            public function getTotalRefundedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
            public function getTotalRefundedAmountByWorkItemId(string $workItemId): Money { return Money::zero(); }
            public function listByNoteId(string $noteId): array { return []; }
        };
    }

    private function legacyPayments(int $amount): PaymentAllocationReaderPort
    {
        return new class($amount) implements PaymentAllocationReaderPort {
            public function __construct(private readonly int $amount) {}
            public function getTotalAllocatedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
        };
    }

    private function legacyRefunds(int $amount): CustomerRefundReaderPort
    {
        return new class($amount) implements CustomerRefundReaderPort {
            public function __construct(private readonly int $amount) {}
            public function getTotalRefundedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
        };
    }

    private function time(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-05-13 10:00:00');
    }
}
