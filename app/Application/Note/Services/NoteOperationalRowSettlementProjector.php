<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class NoteOperationalRowSettlementProjector
{
    public function __construct(
        private readonly PaymentComponentAllocationReaderPort $componentPayments,
        private readonly RefundComponentAllocationReaderPort $componentRefunds,
        private readonly PaymentAllocationReaderPort $legacyPayments,
        private readonly CustomerRefundReaderPort $legacyRefunds,
        private readonly NoteOperationalComponentAllocationTotalsGrouper $totalsGrouper,
        private readonly NoteOperationalComponentSettlementSummaryBuilder $componentSummaryBuilder,
        private readonly NoteOperationalLegacySettlementSummaryBuilder $legacySummaryBuilder,
    ) {
    }

    /**
     * @param array<int, WorkItem> $rows
     * @return array<string, array<string, int|string>>
     */
    public function build(string $noteId, array $rows): array
    {
        usort($rows, static fn (WorkItem $left, WorkItem $right): int => $left->lineNo() <=> $right->lineNo());

        $paymentTotals = $this->totalsGrouper->paymentTotals($this->componentPayments->listByNoteId($noteId));
        $refundTotals = $this->totalsGrouper->refundTotals($this->componentRefunds->listByNoteId($noteId));

        $totalAllocated = $this->legacyPayments->getTotalAllocatedAmountByNoteId($noteId);
        $totalAllocated->ensureNotNegative('Total alokasi pada note tidak boleh negatif.');

        $totalRefunded = $this->legacyRefunds->getTotalRefundedAmountByNoteId($noteId);
        $totalRefunded->ensureNotNegative('Total refund pada note tidak boleh negatif.');

        if ($paymentTotals !== [] || $refundTotals !== []) {
            $this->mergeNoteLevelRemainders(
                $rows,
                $paymentTotals,
                $refundTotals,
                max($totalAllocated->amount() - array_sum($paymentTotals), 0),
                max($totalRefunded->amount() - array_sum($refundTotals), 0),
            );

            return $this->componentSummaryBuilder->build($rows, $paymentTotals, $refundTotals);
        }

        return $this->legacySummaryBuilder->build(
            $rows,
            $totalAllocated->amount(),
            $totalRefunded->amount(),
        );
    }
    /**
     * @param array<int, WorkItem> $rows
     * @param array<string, int> $paymentTotals
     * @param array<string, int> $refundTotals
     */
    private function mergeNoteLevelRemainders(
        array $rows,
        array &$paymentTotals,
        array &$refundTotals,
        int $allocatedRemainder,
        int $refundedRemainder,
    ): void {
        foreach ($rows as $item) {
            $workItemId = $item->id();
            $subtotal = $item->subtotalRupiah()->amount();
            $currentAllocated = $paymentTotals[$workItemId] ?? 0;
            $allocationRoom = max($subtotal - $currentAllocated, 0);
            $allocated = min($allocatedRemainder, $allocationRoom);

            if ($allocated > 0) {
                $paymentTotals[$workItemId] = $currentAllocated + $allocated;
                $allocatedRemainder -= $allocated;
            }

            $currentRefunded = $refundTotals[$workItemId] ?? 0;
            $refundable = max(($paymentTotals[$workItemId] ?? 0) - $currentRefunded, 0);
            $refunded = min($refundedRemainder, $refundable);

            if ($refunded > 0) {
                $refundTotals[$workItemId] = $currentRefunded + $refunded;
                $refundedRemainder -= $refunded;
            }
        }
    }

}
