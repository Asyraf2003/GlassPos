<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Note\Services\AutoCloseNoteWhenFullyPaid;
use App\Application\Payment\DTO\RecordedNotePayment;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationWriterPort;
use App\Ports\Out\UuidPort;

final class RecordAndAllocateNotePaymentOperation
{
    public function __construct(
        private readonly CustomerPaymentWriterPort $payments,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly PaymentComponentAllocationWriterPort $allocationWriter,
        private readonly NoteReaderPort $notes,
        private readonly PaymentAllocationPolicy $policy,
        private readonly ResolveNotePayableComponents $components,
        private readonly AllocatePaymentAcrossComponents $allocator,
        private readonly AutoCloseNoteWhenFullyPaid $autoClose,
        private readonly UuidPort $uuid,
        private readonly BuildCustomerPaymentCashDetail $cashDetails,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function execute(
        string $noteId,
        int $amountRupiah,
        string $paidAt,
        array $selectedRowIds = [],
        string $paymentMethod = CustomerPayment::METHOD_UNKNOWN,
        ?int $amountReceivedRupiah = null,
    ): RecordedNotePayment {
        $note = $this->notes->getByIdForUpdate(trim($noteId))
            ?? throw new DomainException('Target payment allocation tidak ditemukan.');

        $amount = Money::fromInt($amountRupiah);
        $payment = CustomerPayment::create(
            $this->uuid->generate(),
            $amount,
            PaymentDateParser::parseYmd(
                $paidAt,
                'Paid at pada customer payment wajib berupa tanggal yang valid dengan format Y-m-d.'
            ),
            $paymentMethod,
        );

        $cashDetail = $this->cashDetails->execute($payment, $amount, $amountReceivedRupiah);

        $grossAllocatedByNote = $this->allocations->getTotalAllocatedAmountByNoteId($note->id());
        $refundedByNote = $this->refunds->getTotalRefundedAmountByNoteId($note->id());
        $netAllocatedByNote = Money::fromInt(max($grossAllocatedByNote->amount() - $refundedByNote->amount(), 0));

        $this->policy->assertAllocatable(
            $amount,
            $payment->amountRupiah(),
            Money::zero(),
            $note->totalRupiah(),
            $netAllocatedByNote,
        );

        $components = $selectedRowIds === []
            ? $this->components->fromNote($note)
            : $this->components->fromSelectedRows($note, $selectedRowIds);

        $allocations = $this->allocator->allocate($payment->id(), $note->id(), $amount, $components);

        $this->payments->create($payment, $cashDetail);
        $this->allocationWriter->createMany($allocations);
        $this->autoClose->closeIfEligible($note, $payment->id());

        return new RecordedNotePayment($payment, count($allocations));
    }
}
