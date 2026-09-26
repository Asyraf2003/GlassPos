<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Inventory\Services\AutoReverseRefundedStoreStockInventory;
use App\Application\Payment\DTO\SelectedRowsRefundPlan;

final class RecordSelectedRowsRefundPlanBucketProcessor
{
    public function __construct(
        private readonly RecordCustomerRefundOperation $operation,
        private readonly AutoReverseRefundedStoreStockInventory $reverseInventory,
    ) {}

    public function process(SelectedRowsRefundPlan $plan, string $refundedAt, string $reason): array
    {
        $refundIds = [];
        $allocationCount = 0;

        foreach ($plan->paymentBuckets() as $bucket) {
            $recorded = $this->operation->execute(
                $bucket->customerPaymentId(),
                $plan->noteId(),
                $bucket->amountRupiah(),
                $refundedAt,
                $reason,
                $bucket->rowIds(),
            );

            $refund = $recorded->refund();
            $this->reverseInventory->executeFullRowReversal($refund, $plan->stockReturns());

            $refundIds[] = $refund->id();
            $allocationCount += $recorded->allocationCount();
        }

        return [
            'refund_ids' => $refundIds,
            'allocation_count' => $allocationCount,
        ];
    }
}
