<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentAmountResolver;
use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateTransactionWorkspaceInlinePaymentAmountResolverTest extends TestCase
{
    public function test_pay_full_rejects_when_gross_payment_already_covers_revised_total(): void
    {
        $resolver = new CreateTransactionWorkspaceInlinePaymentAmountResolver(
            $this->payments(200000, 300000),
            $this->refunds(0),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nota sudah tidak memiliki sisa tagihan.');

        $resolver->resolve($this->note(250000), ['decision' => 'pay_full']);
    }

    public function test_pay_partial_uses_gross_based_outstanding_not_capped_allocations(): void
    {
        $resolver = new CreateTransactionWorkspaceInlinePaymentAmountResolver(
            $this->payments(200000, 240000),
            $this->refunds(0),
        );

        $this->assertSame(5000, $resolver->resolve($this->note(250000), [
            'decision' => 'pay_partial',
            'amount_paid_rupiah' => 5000,
        ]));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nominal pembayaran sebagian harus lebih kecil dari sisa tagihan.');

        $resolver->resolve($this->note(250000), [
            'decision' => 'pay_partial',
            'amount_paid_rupiah' => 10000,
        ]);
    }

    private function note(int $total): Note
    {
        return Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-15'),
            Money::fromInt($total),
        );
    }

    private function payments(int $allocated, int $gross): PaymentAllocationReaderPort
    {
        return new class($allocated, $gross) implements PaymentAllocationReaderPort {
            public function __construct(private readonly int $allocated, private readonly int $gross) {}
            public function getTotalAllocatedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->allocated); }
            public function getTotalPaymentAmountByNoteId(string $noteId): Money { return Money::fromInt($this->gross); }
            public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
        };
    }

    private function refunds(int $amount): CustomerRefundReaderPort
    {
        return new class($amount) implements CustomerRefundReaderPort {
            public function __construct(private readonly int $amount) {}
            public function getTotalRefundedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalCurrentRefundedAmountByNoteId(string $noteId): Money { return Money::fromInt($this->amount); }
            public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
        };
    }
}
