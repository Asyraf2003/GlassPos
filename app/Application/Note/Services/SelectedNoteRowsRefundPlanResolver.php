<?php
declare(strict_types=1);
namespace App\Application\Note\Services;

use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Payment\Services\PaymentComponentSelectionIds;
use App\Application\Payment\Services\RefundComponentTypePolicy;
use App\Application\Shared\DTO\Result;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
final class SelectedNoteRowsRefundPlanResolver
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteOperationalRowSettlementProjector $settlements,
        private readonly PaymentComponentAllocationReaderPort $allocations,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly SelectedRowsRefundBucketsBuilder $buckets,
        private readonly SelectedNoteRowsRefundEligibilityGuard $eligibility,
    ) {}
    /** @param list<string> $selectedRowIds */
    public function resolve(string $noteId, array $selectedRowIds): Result
    {
        $note = $this->notes->getById(trim($noteId));
        if ($note === null) {
            return Result::failure('Nota tidak ditemukan.', ['refund' => ['REFUND_INVALID_TARGET']]);
        }
        $selectedIds = PaymentComponentSelectionIds::normalize($selectedRowIds);
        $selectedWorkItemIds = PaymentComponentSelectionIds::workItemIds($selectedIds);
        if ($selectedIds === []) {
            return Result::failure('Minimal satu line wajib dipilih.', ['refund' => ['INVALID_SELECTED_ROWS']]);
        }
        $itemsById = [];
        foreach ($note->workItems() as $item) {
            $itemsById[$item->id()] = $item;
        }
        $settlements = $this->settlements->build($note->id(), $note->workItems());
        $ineligible = $this->eligibility->validate($selectedWorkItemIds, $itemsById, $settlements);
        if ($ineligible instanceof Result) {
            return $ineligible;
        }
        $paymentAllocations = $this->allocations->listByNoteId($note->id());
        $paymentBuckets = $this->buckets->build(
            $selectedIds,
            $paymentAllocations,
            $this->refunds->listByNoteId($note->id()),
        );
        if ($paymentBuckets === []) {
            return Result::failure(
                'Tidak ada komponen refund yang eligible sesuai kebijakan.',
                ['refund' => ['NO_REFUNDABLE_COMPONENTS']]
            );
        }
        $paidRowIds = [];
        foreach ($paymentBuckets as $bucket) {
            $paidRowIds = array_merge($paidRowIds, PaymentComponentSelectionIds::workItemIds($bucket->rowIds()));
        }
        $unpaidRowIds = array_values(array_diff($selectedWorkItemIds, array_values(array_unique($paidRowIds))));
        $plan = SelectedRowsRefundPlan::create(
            $note->id(),
            $selectedIds,
            $unpaidRowIds,
            $paymentBuckets,
            $this->cancellableRowIds($selectedIds, $paymentAllocations),
        );
        return Result::success([
            'plan' => $plan,
            'plan_array' => $plan->toArray(),
        ]);
    }
    /** @param list<string> $selectedIds @param list<PaymentComponentAllocation> $allocations @return list<string> */
    private function cancellableRowIds(array $selectedIds, array $allocations): array
    {
        $cancellable = [];
        foreach ($selectedIds as $selectedId) {
            if (PaymentComponentSelectionIds::isComponentSelector($selectedId)) {
                continue;
            }
            $rowAllocations = array_values(array_filter(
                $allocations,
                static fn (PaymentComponentAllocation $allocation): bool =>
                    $allocation->workItemId() === $selectedId,
            ));
            if ($rowAllocations === []) {
                continue;
            }
            foreach ($rowAllocations as $allocation) {
                if (! RefundComponentTypePolicy::isDefaultRefundable($allocation->componentType())) {
                    continue 2;
                }
            }
            $cancellable[] = $selectedId;
        }
        return array_values(array_unique($cancellable));
    }
}
