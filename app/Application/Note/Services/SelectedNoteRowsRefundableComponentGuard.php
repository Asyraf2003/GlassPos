<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\PaymentComponentSelectionIds;
use App\Application\Payment\Services\RefundComponentTypePolicy;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;

final class SelectedNoteRowsRefundableComponentGuard
{
    /**
     * Every submitted selector must contribute at least one structurally refundable component.
     *
     * A package selector may include blocked service-fee components when it also contains
     * a refundable store-stock component. A fully blocked selector must not be ignored.
     *
     * @param list<string> $selectedIds
     * @param list<PaymentComponentAllocation> $paymentAllocations
     */
    public function allSelectedIdsContribute(array $selectedIds, array $paymentAllocations): bool
    {
        foreach ($selectedIds as $selectedId) {
            if (! $this->hasRefundableComponent($selectedId, $paymentAllocations)) {
                return false;
            }
        }

        return true;
    }

    /** @param list<PaymentComponentAllocation> $paymentAllocations */
    private function hasRefundableComponent(string $selectedId, array $paymentAllocations): bool
    {
        foreach ($paymentAllocations as $allocation) {
            if (PaymentComponentSelectionIds::matchingIds($allocation, [$selectedId]) === []) {
                continue;
            }

            if (RefundComponentTypePolicy::isSelectedRowRefundable($allocation->componentType())) {
                return true;
            }
        }

        return false;
    }
}
