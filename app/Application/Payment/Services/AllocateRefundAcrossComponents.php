<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\RefundComponentAllocation\RefundComponentAllocation;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\UuidPort;

final class AllocateRefundAcrossComponents
{
    public function __construct(
        private readonly PaymentComponentAllocationReaderPort $payments,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly LegacyPaymentComponentAllocationSynthesizer $legacyAllocations,
        private readonly UuidPort $uuid,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<RefundComponentAllocation>
     */
    public function allocate(
        string $customerRefundId,
        string $customerPaymentId,
        string $noteId,
        Money $amount,
        array $selectedRowIds = [],
    ): array {
        $remaining = $amount->amount();
        $allocations = [];
        $paymentAllocations = $this->paymentAllocations($customerPaymentId, $noteId, $selectedRowIds);
        $alreadyRefunded = RefundedComponentTotals::build($this->refunds, $customerPaymentId, $noteId);
        $priority = 1;

        if ($selectedRowIds !== [] && $paymentAllocations === []) {
            throw new DomainException('Tidak ada komponen payment yang bisa direfund.');
        }

        foreach ($paymentAllocations as $paymentAllocation) {
            $key = ExistingPaymentComponentTotals::key($paymentAllocation->componentType(), $paymentAllocation->componentRefId());
            $available = max($paymentAllocation->allocatedAmountRupiah()->amount() - ($alreadyRefunded[$key] ?? 0), 0);
            $take = min($remaining, $available);

            if ($take <= 0) {
                continue;
            }

            $allocations[] = RefundComponentAllocation::create(
                $this->uuid->generate(),
                $customerRefundId,
                $customerPaymentId,
                $noteId,
                $paymentAllocation->workItemId(),
                $paymentAllocation->componentType(),
                $paymentAllocation->componentRefId(),
                Money::fromInt($take),
                $priority++,
            );

            $alreadyRefunded[$key] = ($alreadyRefunded[$key] ?? 0) + $take;
            $remaining -= $take;

            if ($remaining === 0) {
                break;
            }
        }

        if ($allocations === []) {
            throw new DomainException('Tidak ada komponen payment yang bisa direfund.');
        }

        if ($remaining > 0) {
            throw new DomainException('Refund tidak bisa dialokasikan penuh ke komponen payment.');
        }

        return $allocations;
    }

    /** @param list<string> $selectedRowIds */
    private function paymentAllocations(string $customerPaymentId, string $noteId, array $selectedRowIds): array
    {
        $paymentAllocations = RefundablePaymentAllocations::forPayment(
            $this->payments,
            $customerPaymentId,
            $noteId,
            $selectedRowIds,
        );

        return $paymentAllocations !== []
            ? $paymentAllocations
            : $this->legacyAllocations->forPayment($customerPaymentId, $noteId, $selectedRowIds);
    }
}
