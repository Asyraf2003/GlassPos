<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;

final class RefundablePaymentAllocations
{
    /**
     * @param list<string> $selectedRowIds
     * @return list<PaymentComponentAllocation>
     */
    public static function forPayment(
        PaymentComponentAllocationReaderPort $reader,
        string $customerPaymentId,
        string $noteId,
        array $selectedRowIds = [],
    ): array {
        $selectedIds = PaymentComponentSelectionIds::normalize($selectedRowIds);

        $allocations = array_filter(
            $reader->listByNoteId($noteId),
            static function (PaymentComponentAllocation $allocation) use ($customerPaymentId, $selectedIds): bool {
                if ($allocation->customerPaymentId() !== $customerPaymentId) {
                    return false;
                }

                if (! PaymentComponentSelectionIds::matches($allocation, $selectedIds)) {
                    return false;
                }

                return $selectedIds !== []
                    ? RefundComponentTypePolicy::isSelectedRowRefundable($allocation->componentType())
                    : RefundComponentTypePolicy::isDefaultRefundable($allocation->componentType());
            },
        );

        usort(
            $allocations,
            static function (PaymentComponentAllocation $left, PaymentComponentAllocation $right): int {
                return $right->allocationPriority() <=> $left->allocationPriority();
            },
        );

        return $allocations;
    }
}
