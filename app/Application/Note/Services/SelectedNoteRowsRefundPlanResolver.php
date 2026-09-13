<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\LegacyPaymentComponentAllocationSynthesizer;
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
        private readonly LegacyPaymentComponentAllocationSynthesizer $legacyAllocations,
        private readonly SelectedRowsRefundBucketsBuilder $buckets,
        private readonly SelectedNoteRowsRefundEligibilityGuard $eligibility,
        private readonly SelectedNoteRowsRefundPlanFactory $planFactory,
    ) {
    }

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

        $paymentAllocations = $this->paymentAllocations($note->id());

        if (! $this->everySelectionHasRefundableComponent($selectedIds, $paymentAllocations)) {
            return Result::failure(
                'Tidak ada komponen refund yang eligible sesuai kebijakan.',
                ['refund' => ['NO_REFUNDABLE_COMPONENTS']],
            );
        }

        $paymentBuckets = $this->buckets->build(
            $selectedIds,
            $paymentAllocations,
            $this->refunds->listByNoteId($note->id()),
        );

        if ($paymentBuckets === []) {
            return Result::failure(
                'Tidak ada komponen refund yang eligible sesuai kebijakan.',
                ['refund' => ['NO_REFUNDABLE_COMPONENTS']],
            );
        }

        $plan = $this->planFactory->build(
            $note->id(),
            $selectedIds,
            $selectedWorkItemIds,
            $paymentAllocations,
            $paymentBuckets,
        );

        return Result::success(['plan' => $plan, 'plan_array' => $plan->toArray()]);
    }

    /**
     * Every submitted selector must contribute at least one structurally refundable component.
     *
     * A package row may still contain blocked service-fee components as long as it also contains
     * a refundable store-stock component. A fully blocked row (service-only/external-purchase)
     * must not be silently ignored when it is mixed with a valid refundable row.
     *
     * @param list<string> $selectedIds
     * @param list<PaymentComponentAllocation> $paymentAllocations
     */
    private function everySelectionHasRefundableComponent(array $selectedIds, array $paymentAllocations): bool
    {
        foreach ($selectedIds as $selectedId) {
            $hasRefundableComponent = false;

            foreach ($paymentAllocations as $allocation) {
                if (PaymentComponentSelectionIds::matchingIds($allocation, [$selectedId]) === []) {
                    continue;
                }

                if (! RefundComponentTypePolicy::isSelectedRowRefundable($allocation->componentType())) {
                    continue;
                }

                $hasRefundableComponent = true;
                break;
            }

            if (! $hasRefundableComponent) {
                return false;
            }
        }

        return true;
    }

    /** @return list<PaymentComponentAllocation> */
    private function paymentAllocations(string $noteId): array
    {
        $paymentAllocations = $this->allocations->listByNoteId($noteId);

        return $paymentAllocations !== [] ? $paymentAllocations : $this->legacyAllocations->forNote($noteId);
    }
}
