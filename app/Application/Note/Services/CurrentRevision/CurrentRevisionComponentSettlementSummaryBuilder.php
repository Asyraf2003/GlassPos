<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use App\Application\Note\Services\NoteOperationalSettlementLabelResolver;
use App\Core\Note\Revision\NoteRevisionLineSnapshot;

final class CurrentRevisionComponentSettlementSummaryBuilder
{
    public function __construct(
        private readonly NoteOperationalSettlementLabelResolver $labels,
        private readonly CurrentRevisionComponentCollectibleSettlementBuilder $collectibleSettlement,
    ) {
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @param array<string, int> $paymentTotals
     * @param array<string, int> $refundTotals
     * @param array<string, int> $componentPaymentTotals
     * @param array<string, int> $componentRefundTotals
     * @return array<string, array<string, int|string>>
     */
    public function build(
        array $lines,
        array $paymentTotals,
        array $refundTotals,
        array $componentPaymentTotals,
        array $componentRefundTotals,
    ): array {
        $summary = [];

        foreach ($lines as $line) {
            $key = $line->workItemRootId() ?? $line->id();
            $subtotal = $line->subtotalRupiah();
            $allocated = (int) ($paymentTotals[$key] ?? 0);
            $refunded = (int) ($refundTotals[$key] ?? 0);
            $componentSettlement = $this->collectibleSettlement->build(
                $line,
                $componentPaymentTotals,
                $componentRefundTotals,
            );
            $rowNetPaid = min(max($allocated - $refunded, 0), $subtotal);
            $netPaid = max($componentSettlement['net_paid_rupiah'], $rowNetPaid);
            $outstanding = min(
                $componentSettlement['outstanding_rupiah'],
                max($subtotal - $netPaid, 0),
            );

            $summary[$key] = [
                'allocated_rupiah' => $allocated,
                'refunded_rupiah' => $refunded,
                'net_paid_rupiah' => $netPaid,
                'outstanding_rupiah' => $outstanding,
                'settlement_label' => $outstanding <= 0
                    ? 'lunas'
                    : $this->labels->resolve($subtotal, $netPaid),
            ];
        }

        return $summary;
    }
}
