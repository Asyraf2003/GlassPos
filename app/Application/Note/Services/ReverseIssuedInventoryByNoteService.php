<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Inventory\Services\ReverseIssuedInventoryOperation;
use App\Core\Note\Note\Note;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use DateTimeImmutable;

final class ReverseIssuedInventoryByNoteService
{
    private const REFUND_REVERSE_SOURCE_TYPE = 'work_item_store_stock_line_reversal';

    public function __construct(
        private readonly ReverseIssuedInventoryOperation $reverseIssuedInventory,
        private readonly InventoryMovementReaderPort $movements,
        private readonly RefundComponentAllocationReaderPort $refunds,
    ) {}

    public function execute(
        Note $note,
        DateTimeImmutable $date,
        string $reverseSourceType = 'transaction_workspace_updated',
    ): int {
        $reversedCount = 0;
        $refunds = $this->refunds->listByNoteId($note->id());

        foreach ($note->workItems() as $workItem) {
            foreach ($workItem->storeStockLines() as $line) {
                $componentRefunds = array_filter($refunds,
                    static fn ($refund): bool => $refund->workItemId() === $workItem->id()
                        && ($refund->componentType() === PaymentComponentType::PRODUCT_ONLY_WORK_ITEM
                            || ($refund->componentType() === PaymentComponentType::SERVICE_STORE_STOCK_PART
                                && $refund->componentRefId() === $line->id()))
                );
                $refunded = array_sum(array_map(static fn ($refund): int => $refund->refundedAmountRupiah()->amount(), $componentRefunds));
                $componentTotal = $workItem->transactionType() === 'store_stock_sale_only'
                    ? $workItem->subtotalRupiah()->amount() : $line->lineTotalRupiah()->amount();
                if ($this->alreadyReversedByRefund($line->id()) || ($componentTotal > 0 && $refunded >= $componentTotal)) {
                    continue;
                }

                $reversedCount += count(
                    $this->reverseIssuedInventory->execute(
                        'work_item_store_stock_line',
                        $line->id(),
                        $date,
                        $reverseSourceType,
                    )
                );
            }
        }

        return $reversedCount;
    }

    private function alreadyReversedByRefund(string $storeStockLineId): bool
    {
        foreach ($this->movements->getBySource(self::REFUND_REVERSE_SOURCE_TYPE, $storeStockLineId) as $movement) {
            if ($movement->qtyDelta() > 0) {
                return true;
            }
        }

        return false;
    }
}
