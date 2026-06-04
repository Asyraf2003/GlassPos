<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Policies;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Core\Note\Note\Note;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NotePaidStatusPolicyTest extends TestCase
{
    public function test_it_treats_zero_total_note_as_not_paid(): void
    {
        $policy = new NotePaidStatusPolicy(
            new class () implements PaymentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(100000);
                }

                public function getTotalPaymentAmountByNoteId(string $noteId): Money
                {
                    return $this->getTotalAllocatedAmountByNoteId($noteId);
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
            new class () implements CustomerRefundReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-16'),
            Money::zero(),
            [],
        );

        $this->assertFalse($policy->isPaid($note));
    }

    public function test_it_treats_note_as_paid_when_current_net_settlement_reaches_total(): void
    {
        $policy = new NotePaidStatusPolicy(
            new class () implements PaymentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(50000);
                }

                public function getTotalPaymentAmountByNoteId(string $noteId): Money
                {
                    return $this->getTotalAllocatedAmountByNoteId($noteId);
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
            new class () implements CustomerRefundReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(20000);
                }

                public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(20000);
                }

                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-16'),
            Money::fromInt(30000),
            [],
        );

        $this->assertTrue($policy->isPaid($note));
    }

    public function test_it_treats_gross_payment_as_paid_when_active_allocations_are_capped(): void
    {
        $policy = new NotePaidStatusPolicy(
            new class () implements PaymentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(200000);
                }

                public function getTotalPaymentAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(300000);
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
            new class () implements CustomerRefundReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-16'),
            Money::fromInt(250000),
            [],
        );

        $this->assertTrue($policy->isPaid($note));
    }

    public function test_it_treats_note_as_not_paid_when_current_refund_reduces_settlement_below_total(): void
    {
        $policy = new NotePaidStatusPolicy(
            new class () implements PaymentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(50000);
                }

                public function getTotalPaymentAmountByNoteId(string $noteId): Money
                {
                    return $this->getTotalAllocatedAmountByNoteId($noteId);
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
            new class () implements CustomerRefundReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(20000);
                }

                public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(20000);
                }

                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-16'),
            Money::fromInt(50000),
            [],
        );

        $this->assertFalse($policy->isPaid($note));
    }

    public function test_it_treats_carry_forward_current_settlement_as_paid_without_subtracting_historical_refund_again(): void
    {
        $policy = new NotePaidStatusPolicy(
            new class () implements PaymentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(200000);
                }

                public function getTotalPaymentAmountByNoteId(string $noteId): Money
                {
                    return $this->getTotalAllocatedAmountByNoteId($noteId);
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
            new class () implements CustomerRefundReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::fromInt(100000);
                }

                public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }
            },
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi Santoso',
            null,
            new DateTimeImmutable('2026-03-16'),
            Money::fromInt(200000),
            [],
        );

        $this->assertTrue($policy->isPaid($note));
    }
}
