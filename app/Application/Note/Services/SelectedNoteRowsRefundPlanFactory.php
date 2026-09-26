<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Payment\Services\PaymentComponentSelectionIds;
use App\Application\Payment\Services\RefundComponentTypePolicy;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;

final class SelectedNoteRowsRefundPlanFactory
{
    /**
     * @param  list<string>  $selectedIds
     * @param  list<string>  $selectedWorkItemIds
     * @param  list<PaymentComponentAllocation>  $paymentAllocations
     * @param  list<object>  $paymentBuckets
     */
    public function build(
        string $noteId,
        array $selectedIds,
        array $selectedWorkItemIds,
        array $paymentAllocations,
        array $paymentBuckets,
        array $stockReturns = [],
    ): SelectedRowsRefundPlan {
        $paidRowIds = [];

        foreach ($paymentBuckets as $bucket) {
            $paidRowIds = array_merge($paidRowIds, PaymentComponentSelectionIds::workItemIds($bucket->rowIds()));
        }

        return SelectedRowsRefundPlan::create(
            $noteId,
            $selectedIds,
            array_values(array_diff($selectedWorkItemIds, array_values(array_unique($paidRowIds)))),
            $paymentBuckets,
            $this->cancellableRowIds($selectedIds, $paymentAllocations),
            $stockReturns,
        );
    }

    /**
     * @param  list<string>  $selectedIds
     * @param  list<PaymentComponentAllocation>  $allocations
     * @return list<string>
     */
    private function cancellableRowIds(array $selectedIds, array $allocations): array
    {
        $cancellable = [];

        foreach ($selectedIds as $selectedId) {
            if (PaymentComponentSelectionIds::isComponentSelector($selectedId)) {
                continue;
            }

            $rowAllocations = $this->rowAllocations($selectedId, $allocations);
            if ($rowAllocations !== [] && $this->allSelectedRowRefundable($rowAllocations)) {
                $cancellable[] = $selectedId;
            }
        }

        return array_values(array_unique($cancellable));
    }

    /**
     * @param  list<PaymentComponentAllocation>  $allocations
     * @return list<PaymentComponentAllocation>
     */
    private function rowAllocations(string $rowId, array $allocations): array
    {
        return array_values(array_filter(
            $allocations,
            static fn (PaymentComponentAllocation $allocation): bool => $allocation->workItemId() === $rowId,
        ));
    }

    /** @param list<PaymentComponentAllocation> $allocations */
    private function allSelectedRowRefundable(array $allocations): bool
    {
        foreach ($allocations as $allocation) {
            if (! RefundComponentTypePolicy::isSelectedRowRefundable($allocation->componentType())) {
                return false;
            }
        }

        return true;
    }
}
