<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\AllocatePaymentAcrossComponents;
use App\Application\Payment\Services\PaymentDateParser;
use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Core\Note\Note\Note;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationWriterPort;
use App\Ports\Out\UuidPort;

final class CreateTransactionWorkspaceInlinePaymentRecorder
{
    public function __construct(
        private readonly CustomerPaymentWriterPort $payments,
        private readonly PaymentComponentAllocationWriterPort $componentAllocations,
        private readonly PaymentAllocationReaderPort $paymentAllocations,
        private readonly PaymentAllocationPolicy $policy,
        private readonly UuidPort $uuid,
        private readonly AuditLogPort $audit,
        private readonly CreateTransactionWorkspaceInlinePaymentContextResolver $context,
        private readonly ResolveNotePayableComponents $components,
        private readonly AllocatePaymentAcrossComponents $allocator,
        private readonly AutoCloseNoteWhenFullyPaid $autoClose,
    ) {
    }

    /**
     * @param mixed $payload
     * @return array{decision:string,amount_paid_rupiah:int,change_rupiah:int}
     */
    public function record(Note $note, mixed $payload): array
    {
        $payment = $this->context->resolve($note, $payload);

        if ($payment['decision'] === 'skip') {
            return [
                'decision' => 'skip',
                'amount_paid_rupiah' => 0,
                'change_rupiah' => 0,
            ];
        }

        $money = Money::fromInt($payment['amount_paid_rupiah']);
        $customerPayment = CustomerPayment::create(
            $this->uuid->generate(),
            $money,
            PaymentDateParser::parseYmd($payment['paid_at'], 'Tanggal bayar wajib valid dengan format Y-m-d.'),
        );

        $existingAllocated = $this->paymentAllocations->getTotalAllocatedAmountByNoteId($note->id());

        $this->policy->assertAllocatable(
            $money,
            $customerPayment->amountRupiah(),
            Money::zero(),
            $note->totalRupiah(),
            $existingAllocated,
        );

        $allocations = $this->allocator->allocate(
            $customerPayment->id(),
            $note->id(),
            $money,
            $this->components->fromNote($note),
        );

        $this->payments->create($customerPayment);
        $this->componentAllocations->createMany($allocations);
        $this->autoClose->closeIfEligible($note, $customerPayment->id());

        $this->audit->record('payment_allocated', [
            'payment_id' => $customerPayment->id(),
            'note_id' => $note->id(),
            'amount' => $payment['amount_paid_rupiah'],
            'allocation_count' => count($allocations),
            'source' => 'transaction_workspace',
            'decision' => $payment['decision'],
        ]);

        return [
            'decision' => $payment['decision'],
            'amount_paid_rupiah' => $payment['amount_paid_rupiah'],
            'change_rupiah' => $payment['change_rupiah'],
        ];
    }
}
