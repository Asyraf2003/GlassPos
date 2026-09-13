<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Payment\RefundComponentAllocation\RefundComponentAllocation;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerRefundHistoryReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class NoteRefundTimelineBuilder
{
    public function __construct(
        private readonly CustomerRefundHistoryReaderPort $refunds,
        private readonly RefundComponentAllocationReaderPort $allocations,
        private readonly NoteReaderPort $notes,
        private readonly ProductReaderPort $products,
        private readonly NoteBillingProjectionSupport $labels,
    ) {
    }

    public function build(string $noteId): array
    {
        $note = $this->notes->getById($noteId);
        $items = [];

        foreach ($note?->workItems() ?? [] as $item) {
            $items[$item->id()] = $item;
        }

        $allocations = [];
        foreach ($this->allocations->listByNoteId($noteId) as $allocation) {
            $allocations[$allocation->customerRefundId()][] = $allocation;
        }

        $timeline = [];
        foreach ($this->refunds->listByNoteId($noteId) as $refund) {
            $timeline[] = [
                'id' => $refund->id(),
                'customer_payment_id' => $refund->customerPaymentId(),
                'refunded_at' => $refund->refundedAt()->format('Y-m-d'),
                'amount_rupiah' => $refund->amountRupiah()->amount(),
                'reason' => $refund->reason(),
                'components' => array_map(
                    fn (RefundComponentAllocation $allocation): array => $this->component(
                        $allocation,
                        $items[$allocation->workItemId()] ?? null,
                    ),
                    $allocations[$refund->id()] ?? [],
                ),
            ];
        }

        return $timeline;
    }

    private function component(RefundComponentAllocation $allocation, ?WorkItem $item): array
    {
        return [
            'work_item_id' => $allocation->workItemId(),
            'component_type' => $allocation->componentType(),
            'component_ref_id' => $allocation->componentRefId(),
            'refunded_amount_rupiah' => $allocation->refundedAmountRupiah()->amount(),
            'label' => $this->componentLabel($allocation, $item),
        ];
    }

    private function componentLabel(RefundComponentAllocation $allocation, ?WorkItem $item): string
    {
        if ($item === null) {
            return $this->labels->componentLabel($allocation->componentType());
        }

        if ($allocation->componentType() === PaymentComponentType::SERVICE_FEE) {
            return $item->serviceDetail()?->serviceName() ?? 'Jasa';
        }

        foreach ($item->storeStockLines() as $line) {
            if ($allocation->componentType() === PaymentComponentType::PRODUCT_ONLY_WORK_ITEM
                || $line->id() === $allocation->componentRefId()) {
                return $this->products->getById($line->productId())?->namaBarang() ?? $line->productId();
            }
        }

        foreach ($item->externalPurchaseLines() as $line) {
            if ($line->id() === $allocation->componentRefId()) {
                return $line->costDescription();
            }
        }

        return $this->labels->componentLabel($allocation->componentType());
    }
}
