<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Application\Note\Services\NotePaymentSettlementPreviewResolver;
use App\Core\Note\Note\Note;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NotePaymentGrossSettlementResolverTest extends TestCase
{
    public function test_outstanding_resolver_does_not_charge_when_gross_payment_covers_total(): void
    {
        $resolver = new NoteOutstandingPaymentAmountResolver(
            $this->notes($this->note(250000)),
            $this->payments(200000, 300000),
            $this->refunds(0),
        );

        $result = $resolver->resolveFull('note-1');

        $this->assertTrue($result->isFailure());
        $this->assertSame('Nota sudah lunas.', $result->message());
    }

    public function test_payment_preview_reports_surplus_from_gross_payment(): void
    {
        $resolver = new NotePaymentSettlementPreviewResolver(
            $this->notes($this->note(250000)),
            $this->payments(200000, 300000),
            $this->refunds(0),
        );

        $result = $resolver->preview('note-1');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(300000, $result->data()['net_paid_rupiah']);
        $this->assertSame(0, $result->data()['outstanding_rupiah']);
        $this->assertSame(50000, $result->data()['surplus_rupiah']);
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

    private function notes(Note $note): NoteReaderPort
    {
        return new class($note) implements NoteReaderPort {
            public function __construct(private readonly Note $note) {}
            public function getById(string $id): ?Note { return trim($id) === $this->note->id() ? $this->note : null; }
            public function getByIdForUpdate(string $id): ?Note { return $this->getById($id); }
            public function countAll(): int { return 1; }
        };
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
