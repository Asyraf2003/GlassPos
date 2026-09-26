<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\Services\LegacyPaymentComponentAllocationSynthesizer;
use App\Application\Payment\Services\PaymentComponentSelectionIds;
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
        private readonly SelectedNoteRowsRefundableComponentGuard $refundableComponents,
        private readonly SelectedNoteRowsRefundPlanFactory $planFactory,
        private readonly SelectedRowsRefundStockChoices $stockChoices,
    ) {}

    /** @param list<string> $selectedRowIds */
    public function resolve(string $noteId, array $selectedRowIds, ?array $stockReturns = null): Result
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
        if ($stockReturns !== null && ($invalid = $this->stockChoices->validate($selectedIds, $paymentAllocations, $stockReturns)) !== null) {
            return $invalid;
        }

        if (! $this->refundableComponents->allSelectedIdsContribute($selectedIds, $paymentAllocations)) {
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
            $stockReturns ?? [],
        );

        return Result::success(['plan' => $plan, 'plan_array' => $plan->toArray()]);
    }

    /** @return list<PaymentComponentAllocation> */
    private function paymentAllocations(string $noteId): array
    {
        $paymentAllocations = $this->allocations->listByNoteId($noteId);

        return $paymentAllocations !== [] ? $paymentAllocations : $this->legacyAllocations->forNote($noteId);
    }
}
