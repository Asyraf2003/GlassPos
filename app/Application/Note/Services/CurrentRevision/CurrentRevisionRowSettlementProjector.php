<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use App\Application\Note\Services\NoteOperationalComponentAllocationTotalsGrouper;
use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class CurrentRevisionRowSettlementProjector
{
    public function __construct(
        private readonly PaymentComponentAllocationReaderPort $componentPayments,
        private readonly RefundComponentAllocationReaderPort $componentRefunds,
        private readonly PaymentAllocationReaderPort $legacyPayments,
        private readonly CustomerRefundReaderPort $legacyRefunds,
        private readonly NoteOperationalComponentAllocationTotalsGrouper $totalsGrouper,
        private readonly CurrentRevisionComponentSettlementSummaryBuilder $componentSummary,
        private readonly CurrentRevisionLegacySettlementSummaryBuilder $legacySummary,
    ) {
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @return array<string, array<string, int|string>>
     */
    public function build(string $noteId, array $lines): array
    {
        usort($lines, static fn (NoteRevisionLineSnapshot $a, NoteRevisionLineSnapshot $b): int => $a->lineNo() <=> $b->lineNo());

        $paymentTotals = $this->totalsGrouper->paymentTotals($this->componentPayments->listByNoteId($noteId));
        $refundTotals = $this->totalsGrouper->refundTotals($this->componentRefunds->listByNoteId($noteId));

        $totalAllocated = $this->legacyPayments->getTotalAllocatedAmountByNoteId($noteId)->amount();
        $totalRefunded = $this->legacyRefunds->getTotalRefundedAmountByNoteId($noteId)->amount();

        if ($paymentTotals !== [] || $refundTotals !== []) {
            $this->mergeNoteLevelRemainders(
                $lines,
                $paymentTotals,
                $refundTotals,
                max($totalAllocated - array_sum($paymentTotals), 0),
                max($totalRefunded - array_sum($refundTotals), 0),
            );

            return $this->componentSummary->build($lines, $paymentTotals, $refundTotals);
        }

        return $this->legacySummary->build(
            $lines,
            $totalAllocated,
            $totalRefunded,
        );
    }
    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @param array<string, int> $paymentTotals
     * @param array<string, int> $refundTotals
     */
    private function mergeNoteLevelRemainders(
        array $lines,
        array &$paymentTotals,
        array &$refundTotals,
        int $allocatedRemainder,
        int $refundedRemainder,
    ): void {
        foreach ($lines as $line) {
            $key = $line->workItemRootId() ?? $line->id();
            $subtotal = $line->subtotalRupiah();
            $currentAllocated = $paymentTotals[$key] ?? 0;
            $allocationRoom = max($subtotal - $currentAllocated, 0);
            $allocated = min($allocatedRemainder, $allocationRoom);

            if ($allocated > 0) {
                $paymentTotals[$key] = $currentAllocated + $allocated;
                $allocatedRemainder -= $allocated;
            }

            $currentRefunded = $refundTotals[$key] ?? 0;
            $refundable = max(($paymentTotals[$key] ?? 0) - $currentRefunded, 0);
            $refunded = min($refundedRemainder, $refundable);

            if ($refunded > 0) {
                $refundTotals[$key] = $currentRefunded + $refunded;
                $refundedRemainder -= $refunded;
            }
        }
    }

}
