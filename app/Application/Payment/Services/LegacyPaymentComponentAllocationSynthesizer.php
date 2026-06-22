<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Support\Facades\DB;

final class LegacyPaymentComponentAllocationSynthesizer
{
    public function __construct(
        private readonly NoteReaderPort $notes,
    ) {
    }

    /**
     * @return list<PaymentComponentAllocation>
     */
    public function forNote(string $noteId): array
    {
        $noteId = trim($noteId);
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return [];
        }

        $componentPaymentIds = DB::table('payment_component_allocations')
            ->where('note_id', $noteId)
            ->select('customer_payment_id');

        $legacyAllocations = DB::table('payment_allocations')
            ->where('note_id', $noteId)
            ->whereNotIn('customer_payment_id', $componentPaymentIds)
            ->orderBy('id')
            ->get(['id', 'customer_payment_id', 'amount_rupiah']);

        if ($legacyAllocations->isEmpty()) {
            return [];
        }

        $rows = $note->workItems();
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

                $rowId = $row->id();
                $rowTotal = $row->subtotalRupiah()->amount();
                $rowRoom = max($rowTotal - ($allocatedByRow[$rowId] ?? 0), 0);
                $take = min($remaining, $rowRoom);

                if ($take <= 0) {
                    continue;
                }

                $allocatedByRow[$rowId] = ($allocatedByRow[$rowId] ?? 0) + $take;
                $remaining -= $take;

                if ($row->transactionType() !== WorkItem::TYPE_STORE_STOCK_SALE_ONLY) {
                    continue;
                }

                $synthetic[] = PaymentComponentAllocation::rehydrate(
                    'legacy-pca-' . (string) $legacyAllocation->id . '-' . $rowId,
                    (string) $legacyAllocation->customer_payment_id,
                    $noteId,
                    $rowId,
                    PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
                    $rowId,
                    Money::fromInt($rowTotal),
                    Money::fromInt($take),
                    $priority++,
                );
            }
        }

        return $synthetic;
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PaymentComponentAllocation>
     */
    public function forPayment(string $customerPaymentId, string $noteId, array $selectedRowIds = []): array
    {
        $selectedIds = PaymentComponentSelectionIds::normalize($selectedRowIds);

        $allocations = array_filter(
            $this->forNote($noteId),
            static function (PaymentComponentAllocation $allocation) use ($customerPaymentId, $selectedIds): bool {
                if ($allocation->customerPaymentId() !== trim($customerPaymentId)) {
                    return false;
                }

                if (! PaymentComponentSelectionIds::matches($allocation, $selectedIds)) {
                    return false;
                }

                return RefundComponentTypePolicy::isDefaultRefundable($allocation->componentType());
            },
        );

        usort(
            $allocations,
            static fn (PaymentComponentAllocation $left, PaymentComponentAllocation $right): int =>
                $right->allocationPriority() <=> $left->allocationPriority(),
        );

        return array_values($allocations);
    }
}
