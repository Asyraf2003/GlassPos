<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

final class BreakdownRowMapper
{
    /**
     * @return array<string, int|string|null>
     */
    public function map(object $row): array
    {
        $partsTotal = (int) $row->parts_total_rupiah;
        $serviceFee = (int) $row->service_price_rupiah;
        $storeServiceProfit = (int) $row->package_profit_rupiah;
        $totalServiceComponent = $serviceFee + $storeServiceProfit;
        $sparepartCogs = (int) $row->sparepart_cogs_rupiah;
        $sparepartMargin = $partsTotal - $sparepartCogs;

        return [
            'note_id' => (string) $row->note_id,
            'work_item_id' => (string) $row->work_item_id,
            'transaction_date' => (string) $row->transaction_date,
            'customer_name' => (string) $row->customer_name,
            'package_sold_amount_rupiah' => (int) $row->package_sold_amount_rupiah,
            'parts_total_rupiah' => $partsTotal,
            'service_price_rupiah' => $serviceFee,
            'package_base_service_price_rupiah' => $row->package_base_service_price_rupiah === null
                ? null
                : (int) $row->package_base_service_price_rupiah,
            'package_service_extra_rupiah' => (int) $row->package_service_extra_rupiah,
            'package_profit_rupiah' => $storeServiceProfit,
            'total_service_component_rupiah' => $totalServiceComponent,
            'refunded_product_component_rupiah' => (int) $row->refunded_product_component_rupiah,
            'refunded_service_component_rupiah' => (int) $row->refunded_service_component_rupiah,
            'sparepart_cogs_rupiah' => $sparepartCogs,
            'sparepart_margin_rupiah' => $sparepartMargin,
            'total_package_gross_profit_rupiah' => $sparepartMargin + $storeServiceProfit,
        ];
    }
}
