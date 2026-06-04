<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\NoteOperationalStatusEvaluator;
use App\Application\Note\Services\NoteOperationalStatusResolver;
use App\Core\Note\Note\Note;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NoteOperationalStatusResolverTest extends TestCase
{
    public function test_it_resolves_open_when_net_paid_is_below_total(): void
    {
        $allocations = $this->createMock(PaymentAllocationReaderPort::class);
        $refunds = $this->createMock(CustomerRefundReaderPort::class);

        $allocations->method('getTotalAllocatedAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(20000));
        $allocations->method('getTotalPaymentAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(20000));
        $refunds->method('getTotalRefundedAmountByNoteId')->with('note-1')->willReturn(Money::zero());

        $service = new NoteOperationalStatusResolver(
            $allocations,
            $refunds,
            new NoteOperationalStatusEvaluator(),
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-15'),
            Money::fromInt(50000),
            [],
            Note::STATE_OPEN,
            null,
            null,
            null,
            null,
        );

        $result = $service->resolve($note);

        $this->assertSame('open', $result['operational_status']);
        $this->assertTrue($result['is_open']);
        $this->assertFalse($result['is_close']);
        $this->assertSame(50000, $result['grand_total_rupiah']);
        $this->assertSame(20000, $result['total_allocated_rupiah']);
        $this->assertSame(0, $result['total_refunded_rupiah']);
        $this->assertSame(20000, $result['net_paid_rupiah']);
        $this->assertSame(30000, $result['outstanding_rupiah']);
    }

    public function test_it_resolves_close_when_net_paid_reaches_total(): void
    {
        $allocations = $this->createMock(PaymentAllocationReaderPort::class);
        $refunds = $this->createMock(CustomerRefundReaderPort::class);

        $allocations->method('getTotalAllocatedAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(50000));
        $allocations->method('getTotalPaymentAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(50000));
        $refunds->method('getTotalRefundedAmountByNoteId')->with('note-1')->willReturn(Money::zero());

        $service = new NoteOperationalStatusResolver(
            $allocations,
            $refunds,
            new NoteOperationalStatusEvaluator(),
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-15'),
            Money::fromInt(50000),
            [],
            Note::STATE_CLOSED,
            null,
            null,
            null,
            null,
        );

        $result = $service->resolve($note);

        $this->assertSame('close', $result['operational_status']);
        $this->assertFalse($result['is_open']);
        $this->assertTrue($result['is_close']);
        $this->assertSame(0, $result['outstanding_rupiah']);
    }

    public function test_it_resolves_open_again_when_refund_reduces_net_paid(): void
    {
        $allocations = $this->createMock(PaymentAllocationReaderPort::class);
        $refunds = $this->createMock(CustomerRefundReaderPort::class);

        $allocations->method('getTotalAllocatedAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(50000));
        $allocations->method('getTotalPaymentAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(50000));
        $refunds->method('getTotalRefundedAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(10000));

        $service = new NoteOperationalStatusResolver(
            $allocations,
            $refunds,
            new NoteOperationalStatusEvaluator(),
        );

        $note = Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-15'),
            Money::fromInt(50000),
            [],
            Note::STATE_CLOSED,
            null,
            null,
            null,
            null,
        );

        $result = $service->resolve($note);

        $this->assertSame('open', $result['operational_status']);
        $this->assertTrue($result['is_open']);
        $this->assertFalse($result['is_close']);
        $this->assertSame(40000, $result['net_paid_rupiah']);
        $this->assertSame(10000, $result['outstanding_rupiah']);
    }

    public function test_it_uses_gross_payment_when_active_allocations_are_capped(): void
    {
        $allocations = $this->createMock(PaymentAllocationReaderPort::class);
        $refunds = $this->createMock(CustomerRefundReaderPort::class);

        $allocations->method('getTotalAllocatedAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(200000));
        $allocations->method('getTotalPaymentAmountByNoteId')->with('note-1')->willReturn(Money::fromInt(300000));
        $refunds->method('getTotalRefundedAmountByNoteId')->with('note-1')->willReturn(Money::zero());

        $service = new NoteOperationalStatusResolver($allocations, $refunds, new NoteOperationalStatusEvaluator());

        $result = $service->resolve(Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-15'),
            Money::fromInt(250000),
        ));

        $this->assertSame('close', $result['operational_status']);
        $this->assertSame(300000, $result['net_paid_rupiah']);
        $this->assertSame(0, $result['outstanding_rupiah']);
    }
}
