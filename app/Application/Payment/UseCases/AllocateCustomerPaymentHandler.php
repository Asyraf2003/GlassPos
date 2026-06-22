<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Shared\DTO\Result;
use App\Core\Payment\PaymentAllocation\PaymentAllocation;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\{CustomerPaymentReaderPort, CustomerRefundReaderPort, PaymentAllocationReaderPort, PaymentAllocationWriterPort};
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class AllocateCustomerPaymentHandler
{
    public function __construct(
        private CustomerPaymentReaderPort $customerPayments,
        private PaymentAllocationReaderPort $allocations,
        private CustomerRefundReaderPort $refunds,
        private PaymentAllocationWriterPort $allocationWriter,
        private NoteReaderPort $notes,
        private PaymentAllocationPolicy $policy,
        private TransactionManagerPort $transactions,
        private UuidPort $uuid,
        private AllocatePaymentErrorClassifier $errors,
        private AuditLogPort $audit
    ) {}

    public function handle(string $cpId, string $nId, int $amount): Result
    {
        $started = false;
        try {
            $this->transactions->begin(); $started = true;

            $pay = $this->customerPayments->getById(trim($cpId));
            $note = $this->notes->getById(trim($nId));
            if (!$pay || !$note) throw new DomainException('Target payment allocation tidak ditemukan.');

            $allocAmount = Money::fromInt($amount);
            $grossAllocatedByNote = $this->allocations->getTotalAllocatedAmountByNoteId($note->id());
            $refundedByNote = $this->refunds->getTotalRefundedAmountByNoteId($note->id());
            $netAllocatedByNote = Money::fromInt(max($grossAllocatedByNote->amount() - $refundedByNote->amount(), 0));

            $this->policy->assertAllocatable(
                $allocAmount,
                $pay->amountRupiah(),
                $this->customerPayments->getTotalAllocatedAmountByPaymentId($pay->id()),
                $note->totalRupiah(),
                $netAllocatedByNote
            );

            $allocation = PaymentAllocation::create($this->uuid->generate(), $pay->id(), $note->id(), $allocAmount);
            $this->allocationWriter->create($allocation);

            $this->audit->record('payment_allocated', [
                'payment_id' => $pay->id(), 'note_id' => $note->id(), 'amount' => $amount
            ]);

            $this->transactions->commit();
            return Result::success(['id' => $allocation->id()], 'Payment allocation berhasil dicatat.');

        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return $this->errors->classify($e);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }
}
