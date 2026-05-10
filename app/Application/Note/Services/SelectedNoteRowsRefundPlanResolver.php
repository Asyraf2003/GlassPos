<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class SelectedNoteRowsRefundPlanResolver
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteOperationalRowSettlementProjector $settlements,
        private readonly WorkItemOperationalStatusResolver $statuses,
        private readonly PaymentComponentAllocationReaderPort $allocations,
        private readonly RefundComponentAllocationReaderPort $refunds,
        private readonly SelectedRowsRefundBucketsBuilder $buckets,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function resolve(string $noteId, array $selectedRowIds): Result
    {
        $note = $this->notes->getById(trim($noteId));

        if ($note === null) {
            return Result::failure('Nota tidak ditemukan.', ['refund' => ['REFUND_INVALID_TARGET']]);
        }

        $selectedIds = array_values(array_unique(array_filter(
            $selectedRowIds,
            static fn (string $id): bool => trim($id) !== '',
        )));

        if ($selectedIds === []) {
            return Result::failure('Minimal satu line wajib dipilih.', ['refund' => ['INVALID_SELECTED_ROWS']]);
        }

        $itemsById = [];
        foreach ($note->workItems() as $item) {
            $itemsById[$item->id()] = $item;
        }

        $settlements = $this->settlements->build($note->id(), $note->workItems());

        foreach ($selectedIds as $rowId) {
            if (!isset($itemsById[$rowId])) {
                return Result::failure('Line refund yang dipilih tidak valid untuk nota ini.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }

            if ($this->isAlreadyInactive($itemsById[$rowId], $settlements[$rowId] ?? [])) {
                return Result::failure('Line yang sudah batal/refund tidak boleh dipilih lagi.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }

            if (!$this->isOperationallyClose($itemsById[$rowId], $settlements[$rowId] ?? [])) {
                return Result::failure('Line open/belum lunas tidak boleh direfund.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }
        }

        $paymentBuckets = $this->buckets->build(
            $selectedIds,
            $this->allocations->listByNoteId($note->id()),
            $this->refunds->listByNoteId($note->id()),
        );

        $paidRowIds = [];
        foreach ($paymentBuckets as $bucket) {
            $paidRowIds = array_merge($paidRowIds, $bucket->rowIds());
        }

        $unpaidRowIds = array_values(array_diff($selectedIds, array_values(array_unique($paidRowIds))));
        $plan = SelectedRowsRefundPlan::create($note->id(), $selectedIds, $unpaidRowIds, $paymentBuckets);

        return Result::success([
            'plan' => $plan,
            'plan_array' => $plan->toArray(),
        ]);
    }

    private function isAlreadyInactive(WorkItem $item, array $settlement): bool
    {
        if ($item->status() === WorkItem::STATUS_CANCELED) {
            return true;
        }

        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $item->subtotalRupiah()->amount());

        return $this->statuses->resolve($outstanding, $refunded) === WorkItemOperationalStatusResolver::STATUS_REFUND;
    }

    private function isOperationallyClose(WorkItem $item, array $settlement): bool
    {
        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $item->subtotalRupiah()->amount());

        return $this->statuses->resolve($outstanding, $refunded) === WorkItemOperationalStatusResolver::STATUS_CLOSE;
    }
}
