<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Payment\DTO\RecordedCustomerRefund;
use App\Core\Payment\CustomerRefund\CustomerRefund;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerPaymentReaderPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\CustomerRefundWriterPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationWriterPort;
use App\Ports\Out\UuidPort;

final class RecordCustomerRefundOperation
{
    public function __construct(
        private readonly CustomerPaymentReaderPort $customerPayments,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly CustomerRefundWriterPort $refundWriter,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly RefundComponentAllocationWriterPort $refundAllocationWriter,
        private readonly AllocateRefundAcrossComponents $refundAllocator,
        private readonly NoteReaderPort $notes,
        private readonly UuidPort $uuid,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function execute(
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
        array $selectedRowIds = [],
    ): RecordedCustomerRefund {
        $payment = $this->customerPayments->getById(trim($customerPaymentId));
        $note = $this->notes->getByIdForUpdate(trim($noteId));

        if ($payment === null || $note === null) {
            throw new DomainException('Target refund tidak ditemukan.');
        }

        $amount = Money::fromInt($amountRupiah);

        RefundPairLimitGuard::assertWithinAllocated(
            $this->allocations->getTotalAllocatedAmountByCustomerPaymentIdAndNoteId($payment->id(), $note->id()),
            $this->refunds->getTotalRefundedAmountByCustomerPaymentIdAndNoteId($payment->id(), $note->id()),
            $amount,
        );

        $refund = CustomerRefund::create(
            $this->uuid->generate(),
            $payment->id(),
            $note->id(),
            $amount,
            PaymentDateParser::parseYmd(
                $refundedAt,
                'Refunded at pada customer refund wajib berupa tanggal yang valid dengan format Y-m-d.'
            ),
            trim($reason),
        );

        $refundAllocations = $this->refundAllocator->allocate(
            $refund->id(),
            $payment->id(),
            $note->id(),
            $amount,
            $selectedRowIds,
        );

        $this->refundWriter->create($refund);
        $this->refundAllocationWriter->createMany($refundAllocations);

        return new RecordedCustomerRefund($refund, count($refundAllocations));
    }
}
