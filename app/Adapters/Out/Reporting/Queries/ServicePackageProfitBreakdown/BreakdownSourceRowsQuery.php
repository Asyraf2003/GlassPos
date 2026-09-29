<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BreakdownSourceRowsQuery
{
    public function __construct(
        private readonly BreakdownSourceBaseQuery $source,
    ) {}

    /**
     * @return Collection<int, object>
     */
    public function rows(
        string $fromTransactionDate,
        string $toTransactionDate,
        ?string $asOfDate = null,
        bool $legacyOnly = false,
    ): Collection {
        return $this->source->build($fromTransactionDate, $toTransactionDate, $asOfDate, $legacyOnly)
            ->orderBy('notes.transaction_date')
            ->orderBy('notes.id')
            ->orderBy('work_items.line_no')
            ->get([
                'notes.id as note_id',
                'work_items.id as work_item_id',
                'work_items.line_no as package_line_no',
                'notes.transaction_date',
                'notes.customer_name',
                'work_items.subtotal_rupiah as package_sold_amount_rupiah',
                DB::raw('COALESCE(parts_totals.parts_total_rupiah, 0) as parts_total_rupiah'),
                'work_item_service_details.service_price_rupiah',
                'work_item_service_details.package_base_service_price_rupiah',
                DB::raw('COALESCE(work_item_service_details.package_service_extra_rupiah, 0) as package_service_extra_rupiah'),
                DB::raw('COALESCE(work_item_service_details.package_profit_rupiah, 0) as package_profit_rupiah'),
                DB::raw('COALESCE(refunded_product_components.refunded_product_component_rupiah, 0) as refunded_product_component_rupiah'),
                DB::raw('COALESCE(refunded_service_components.refunded_service_component_rupiah, 0) as refunded_service_component_rupiah'),
                DB::raw('(COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0)) as sparepart_cogs_rupiah'),
            ]);
    }

    public function summary(
        string $fromTransactionDate,
        string $toTransactionDate,
        ?string $asOfDate = null,
        bool $legacyOnly = false,
    ): object {
        return $this->source->build($fromTransactionDate, $toTransactionDate, $asOfDate, $legacyOnly)
            ->selectRaw('COUNT(*) as total_packages')
            ->selectRaw('COALESCE(SUM(work_items.subtotal_rupiah), 0) as package_sold_amount_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(parts_totals.parts_total_rupiah, 0)), 0) as parts_total_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0)), 0) as sparepart_cogs_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(parts_totals.parts_total_rupiah, 0) - (COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0))), 0) as sparepart_margin_rupiah')
            ->selectRaw('COALESCE(SUM(work_item_service_details.service_price_rupiah), 0) as service_fee_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(work_item_service_details.package_profit_rupiah, 0)), 0) as package_profit_rupiah')
            ->selectRaw('COALESCE(SUM(work_item_service_details.service_price_rupiah + COALESCE(work_item_service_details.package_profit_rupiah, 0)), 0) as total_service_component_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(refunded_product_components.refunded_product_component_rupiah, 0)), 0) as refunded_product_component_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(refunded_service_components.refunded_service_component_rupiah, 0)), 0) as refunded_service_component_rupiah')
            ->selectRaw('COALESCE(SUM((COALESCE(parts_totals.parts_total_rupiah, 0) - (COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0))) + COALESCE(work_item_service_details.package_profit_rupiah, 0)), 0) as total_package_gross_profit_rupiah')
            ->first();
    }
}
