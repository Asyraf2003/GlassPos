<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class NoteBillingProjectionComponentRowsBuilder
{
    public function __construct(
        private readonly NoteBillingProjectionComponentRowFactory $rows,
        private readonly NoteBillingProjectionRefundedStoreStockComponentSkipper $refundedStoreStock,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     * @return list<array<string, mixed>>
     */
    public function build(array $row): array
    {
        $components = $this->sortedComponents($row);
        if ($components === []) {
            return [];
        }

        $billing = [];
        $allocatedRemainder = (int) ($row['allocated_rupiah'] ?? 0);
        $refundedRemainder = (int) ($row['refunded_rupiah'] ?? 0);
        $priorOutstanding = 0;

        foreach ($components as $component) {
            $componentRow = $this->componentRow(
                $row,
                $component,
                $allocatedRemainder,
                $refundedRemainder,
                $priorOutstanding,
            );

            if ($componentRow !== null) {
                $billing[] = $componentRow;
            }
        }

        return $billing;
    }

    private function sortedComponents(array $row): array
    {
        $components = is_array($row['billing_components'] ?? null)
            ? array_values(array_filter($row['billing_components'], 'is_array'))
            : [];

        usort(
            $components,
            static fn (array $a, array $b): int => (int) ($a['component_order'] ?? 0) <=> (int) ($b['component_order'] ?? 0),
        );

        return $components;
    }

    private function componentRow(
        array $row,
        array $component,
        int &$allocatedRemainder,
        int &$refundedRemainder,
        int &$priorOutstanding,
    ): ?array {
        $componentType = (string) ($component['component_type'] ?? '');
        $componentRefId = (string) ($component['component_ref_id'] ?? '');
        $total = (int) ($component['component_total_rupiah'] ?? 0);

        if ($componentType === '' || $componentRefId === '' || $total <= 0) {
            return null;
        }

        $allocated = min(max($allocatedRemainder, 0), $total);
        $allocatedRemainder -= $allocated;

        $refunded = min(max($refundedRemainder, 0), $allocated);
        $refundedRemainder -= $refunded;

        if ($this->refundedStoreStock->shouldSkip($componentType, $componentRefId, $refunded)) {
            return null;
        }

        $netPaid = max($allocated - $refunded, 0);
        $outstanding = max($total - $netPaid, 0);
        $blocked = $priorOutstanding > 0 && $outstanding > 0;
        $priorOutstanding += $outstanding;

        return $this->rows->build($row, $component, $componentType, $componentRefId, $total, $allocated, $refunded, $netPaid, $outstanding, $blocked);
    }
}
