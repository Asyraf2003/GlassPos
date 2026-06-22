<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\ValueObjects\Money;

final class LegacyPaymentComponentAllocationBuilder
{
    /**
     * @param list<WorkItem> $rows
     * @param list<object> $legacyAllocations
     * @return list<PaymentComponentAllocation>
     */
    public function build(string $noteId, array $rows, array $legacyAllocations): array
    {
        usort($rows, static fn (WorkItem $left, WorkItem $right): int => $left->lineNo() <=> $right->lineNo());

        $synthetic = [];
        $allocatedByRow = [];
        $priority = 1;

        foreach ($legacyAllocations as $legacyAllocation) {
            $remaining = (int) $legacyAllocation->amount_rupiah;

            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }

                $take = $this->takeForRow($row, $allocatedByRow, $remaining);
                if ($take <= 0) {
                    continue;
                }

                $allocatedByRow[$row->id()] = ($allocatedByRow[$row->id()] ?? 0) + $take;
                $remaining -= $take;

                if ($row->transactionType() !== WorkItem::TYPE_STORE_STOCK_SALE_ONLY) {
                    continue;
                }

                $synthetic[] = $this->allocation($noteId, $legacyAllocation, $row, $take, $priority++);
            }
        }

        return $synthetic;
    }

    /** @param array<string, int> $allocatedByRow */
    private function takeForRow(WorkItem $row, array $allocatedByRow, int $remaining): int
    {
        $rowTotal = $row->subtotalRupiah()->amount();
        $rowRoom = max($rowTotal - ($allocatedByRow[$row->id()] ?? 0), 0);

        return min($remaining, $rowRoom);
    }

    private function allocation(string $noteId, object $legacyAllocation, WorkItem $row, int $take, int $priority): PaymentComponentAllocation
    {
        return PaymentComponentAllocation::rehydrate(
            'legacy-pca-' . (string) $legacyAllocation->id . '-' . $row->id(),
            (string) $legacyAllocation->customer_payment_id,
            $noteId,
            $row->id(),
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            $row->id(),
            Money::fromInt($row->subtotalRupiah()->amount()),
            Money::fromInt($take),
            $priority,
        );
    }
}
