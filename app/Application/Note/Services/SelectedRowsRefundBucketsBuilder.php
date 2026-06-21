<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\DTO\SelectedRowsRefundPaymentBucket;
use App\Application\Payment\Services\PaymentComponentSelectionIds;
use App\Application\Payment\Services\RefundComponentTypePolicy;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\RefundComponentAllocation\RefundComponentAllocation;

final class SelectedRowsRefundBucketsBuilder
{
    /**
     * @param list<string> $selectedRowIds
     * @param list<PaymentComponentAllocation> $allocations
     * @param list<RefundComponentAllocation> $refundAllocations
     * @return list<SelectedRowsRefundPaymentBucket>
     */
    public function build(array $selectedRowIds, array $allocations, array $refundAllocations = []): array
    {
        $selectedIds = PaymentComponentSelectionIds::normalize($selectedRowIds);
        $refunded = $this->refundedByPaymentComponent($refundAllocations);
        $groups = [];

        foreach ($allocations as $allocation) {
            $matchedSelectionIds = PaymentComponentSelectionIds::matchingIds($allocation, $selectedIds);

            if ($matchedSelectionIds === []) {
                continue;
            }

            if (! RefundComponentTypePolicy::isDefaultRefundable($allocation->componentType())) {
                continue;
            }

            $paymentId = $allocation->customerPaymentId();
            $key = $this->paymentComponentKey(
                $paymentId,
                $allocation->componentType(),
                $allocation->componentRefId(),
            );

            $available = max(
                $allocation->allocatedAmountRupiah()->amount() - ($refunded[$key] ?? 0),
                0,
            );

            if ($available === 0) {
                continue;
            }

            $groups[$paymentId] ??= [
                'row_ids' => [],
                'amount_rupiah' => 0,
            ];

            $groups[$paymentId]['row_ids'] = [
                ...$groups[$paymentId]['row_ids'],
                ...$matchedSelectionIds,
            ];
            $groups[$paymentId]['amount_rupiah'] += $available;
        }

        return array_values(array_map(
            static fn (string $paymentId, array $group): SelectedRowsRefundPaymentBucket =>
                SelectedRowsRefundPaymentBucket::create(
                    $paymentId,
                    array_values(array_unique($group['row_ids'])),
                    (int) $group['amount_rupiah'],
                ),
            array_keys($groups),
            array_values($groups),
        ));
    }

    /**
     * @param list<RefundComponentAllocation> $refundAllocations
     * @return array<string,int>
     */
    private function refundedByPaymentComponent(array $refundAllocations): array
    {
        $totals = [];

        foreach ($refundAllocations as $allocation) {
            $key = $this->paymentComponentKey(
                $allocation->customerPaymentId(),
                $allocation->componentType(),
                $allocation->componentRefId(),
            );

            $totals[$key] = ($totals[$key] ?? 0) + $allocation->refundedAmountRupiah()->amount();
        }

        return $totals;
    }

    private function paymentComponentKey(string $paymentId, string $componentType, string $componentRefId): string
    {
        return trim($paymentId) . '|' . trim($componentType) . '|' . trim($componentRefId);
    }
}
