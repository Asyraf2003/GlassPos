<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;

final class CreateTransactionWorkspacePackageAllocationAuditMapper
{
    /**
     * @param array<string, mixed> $item
     * @return list<array<string, mixed>>
     */
    public function from(array $item, WorkItem $workItem): array
    {
        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            return [];
        }

        $serviceDetail = $workItem->serviceDetail();

        if ($serviceDetail === null) {
            return [];
        }

        $allocations = [];

        foreach ($workItem->storeStockLines() as $line) {
            $qty = $line->qty();
            $partTotal = $line->lineTotalRupiah()->amount();
            $allocations[] = [
                'work_item_id' => $workItem->id(),
                'store_stock_line_id' => $line->id(),
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => (int) ($item['package_total_rupiah'] ?? 0),
                'sparepart_total_rupiah' => $partTotal,
                'service_price_rupiah' => $serviceDetail->servicePriceRupiah()->amount(),
                'package_profit_rupiah' => $serviceDetail->packageProfitRupiah()->amount(),
                'package_base_service_price_rupiah' => $serviceDetail->packageBaseServicePriceRupiah()?->amount(),
                'package_service_extra_rupiah' => $serviceDetail->packageServiceExtraRupiah()->amount(),
                'product_id' => $line->productId(),
                'qty' => $qty,
                'product_unit_price_rupiah' => intdiv($partTotal, max(1, $qty)),
            ];
        }

        foreach ($workItem->externalPurchaseLines() as $line) {
            $input = $this->firstLine($item['external_purchase_lines'] ?? []);
            $allocations[] = [
                'work_item_id' => $workItem->id(),
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => (int) ($item['package_total_rupiah'] ?? 0),
                'external_total_rupiah' => $line->lineTotalRupiah()->amount(),
                'service_price_rupiah' => $serviceDetail->servicePriceRupiah()->amount(),
                'source' => 'external_purchase',
                'label' => $this->nullableString($input['label'] ?? null),
                'qty' => is_int($input['qty'] ?? null) ? $input['qty'] : null,
            ];
        }

        return $allocations;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function firstLine(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first) ? $first : [];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
