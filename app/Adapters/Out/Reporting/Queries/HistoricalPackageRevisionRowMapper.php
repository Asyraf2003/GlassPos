<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

final class HistoricalPackageRevisionRowMapper
{
    public function __construct(
        private readonly HistoricalPackageSnapshotPayload $payloads,
    ) {}

    /**
     * @param array<string, int> $cogsByStockLine
     * @param array<string, array{product:int,service:int}> $refundsByWorkItem
     * @return array<string, int|string|null>
     */
    public function map(object $row, array $cogsByStockLine, array $refundsByWorkItem): array
    {
        $workItemId = (string) $row->work_item_id;
        $payload = $this->payloads->decode($row->payload ?? null);
        $parts = $this->payloads->partsTotal($payload);
        $packageTotal = (int) $row->package_sold_amount_rupiah;
        $servicePrice = $this->payloads->servicePrice($payload, $row->service_price_rupiah ?? null);
        $totalService = (int) ($payload['total_service_component_rupiah'] ?? ($packageTotal - $parts));
        $packageProfit = (int) ($payload['package_profit_rupiah'] ?? ($totalService - $servicePrice));
        $cogs = array_sum(array_map(
            static fn (string $id): int => $cogsByStockLine[$id] ?? 0,
            $this->payloads->stockLineIds($payload),
        ));
        $refunds = $refundsByWorkItem[$workItemId] ?? ['product' => 0, 'service' => 0];
        $margin = $parts - $cogs;

        return [
            'note_id' => (string) $row->note_id,
            'work_item_id' => $workItemId,
            'package_line_no' => (int) ($row->package_line_no ?? 0),
            'transaction_date' => (string) $row->transaction_date,
            'customer_name' => (string) $row->customer_name,
            'package_sold_amount_rupiah' => $packageTotal,
            'parts_total_rupiah' => $parts,
            'service_price_rupiah' => $servicePrice,
            'package_base_service_price_rupiah' => array_key_exists('package_base_service_price_rupiah', $payload)
                && $payload['package_base_service_price_rupiah'] !== null
                ? (int) $payload['package_base_service_price_rupiah'] : null,
            'package_service_extra_rupiah' => (int) ($payload['package_service_extra_rupiah'] ?? 0),
            'package_profit_rupiah' => $packageProfit,
            'total_service_component_rupiah' => $servicePrice + $packageProfit,
            'refunded_product_component_rupiah' => $refunds['product'],
            'refunded_service_component_rupiah' => $refunds['service'],
            'sparepart_cogs_rupiah' => $cogs,
            'sparepart_margin_rupiah' => $margin,
            'total_package_gross_profit_rupiah' => $margin + $packageProfit,
        ];
    }
}
