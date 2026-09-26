<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\WorkItem;

final class SelectedNoteRowsRefundEligibilityGuard
{
    public function __construct(
        private readonly WorkItemOperationalStatusResolver $statuses,
    ) {}

    /**
     * @param  list<string>  $selectedIds
     * @param  array<string, WorkItem>  $itemsById
     * @param  array<string, array<string, int>>  $settlements
     */
    public function validate(array $selectedIds, array $itemsById, array $settlements): ?Result
    {
        foreach ($selectedIds as $rowId) {
            $item = $itemsById[$rowId] ?? null;

            if (! $item instanceof WorkItem) {
                return Result::failure('Line refund yang dipilih tidak valid untuk nota ini.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }

            if ($this->isAlreadyInactive($item, $settlements[$rowId] ?? [])) {
                return Result::failure('Line yang sudah batal/refund tidak boleh dipilih lagi.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }

            if (! $this->isOperationallyClose($item, $settlements[$rowId] ?? [])) {
                return Result::failure('Line open/belum lunas tidak boleh direfund.', ['refund' => ['INVALID_SELECTED_ROWS']]);
            }
            if ($item->externalPurchaseLines() !== []) {
                return Result::failure('Refund pembelian luar belum didukung pada jalur ini.', ['refund' => ['EXTERNAL_REFUND_UNSUPPORTED']]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, int>  $settlement
     */
    private function isAlreadyInactive(WorkItem $item, array $settlement): bool
    {
        if ($item->status() === WorkItem::STATUS_CANCELED) {
            return true;
        }

        return $this->status($item, $settlement) === WorkItemOperationalStatusResolver::STATUS_REFUND;
    }

    /**
     * @param  array<string, int>  $settlement
     */
    private function isOperationallyClose(WorkItem $item, array $settlement): bool
    {
        return $this->status($item, $settlement) === WorkItemOperationalStatusResolver::STATUS_CLOSE;
    }

    /**
     * @param  array<string, int>  $settlement
     */
    private function status(WorkItem $item, array $settlement): string
    {
        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $item->subtotalRupiah()->amount());

        return $this->statuses->resolve($outstanding, $refunded);
    }
}
