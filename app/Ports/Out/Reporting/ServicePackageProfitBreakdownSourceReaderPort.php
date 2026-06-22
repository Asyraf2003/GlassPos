<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface ServicePackageProfitBreakdownSourceReaderPort
{
    /**
     * @return list<array{
     *   note_id:string,
     *   work_item_id:string,
     *   transaction_date:string,
     *   customer_name:string,
     *   package_sold_amount_rupiah:int,
     *   parts_total_rupiah:int,
     *   service_price_rupiah:int,
     *   package_base_service_price_rupiah:int|null,
     *   package_service_extra_rupiah:int,
     *   package_profit_rupiah:int,
     *   total_service_component_rupiah:int,
     *   refunded_product_component_rupiah:int,
     *   refunded_service_component_rupiah:int,
     *   sparepart_cogs_rupiah:int,
     *   sparepart_margin_rupiah:int,
     *   total_package_gross_profit_rupiah:int
     * }>
     */
    public function getRows(string $fromTransactionDate, string $toTransactionDate): array;
}
