<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;

final class CreateTransactionWorkspacePackageAllocationAuditMapper
{
    /**
     * @param array<string, mixed> $item
     * @return array{
     *     work_item_id:string,
     *     store_stock_line_id:string,
     *     pricing_mode:string,
     *     package_total_rupiah:int,
     *     sparepart_total_rupiah:int,
     *     service_price_rupiah:int,
     *     product_id:string,
     *     qty:int,
     *     product_unit_price_rupiah:int
     * }|null
     */
    public function from(array $item, WorkItem $workItem): ?array
    {
        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            return null;
        }

        $serviceDetail = $workItem->serviceDetail();
        $storeStockLine = $workItem->storeStockLines()[0] ?? null;

        if ($serviceDetail === null || $storeStockLine === null) {
            return null;
        }

        $qty = $storeStockLine->qty();
        $sparepartTotal = $storeStockLine->lineTotalRupiah()->amount();

        return [
            'work_item_id' => $workItem->id(),
            'store_stock_line_id' => $storeStockLine->id(),
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => (int) ($item['package_total_rupiah'] ?? 0),
            'sparepart_total_rupiah' => $sparepartTotal,
            'service_price_rupiah' => $serviceDetail->servicePriceRupiah()->amount(),
            'product_id' => $storeStockLine->productId(),
            'qty' => $qty,
            'product_unit_price_rupiah' => intdiv($sparepartTotal, max(1, $qty)),
        ];
    }
}
