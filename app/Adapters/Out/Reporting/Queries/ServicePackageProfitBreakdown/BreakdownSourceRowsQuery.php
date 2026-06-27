<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BreakdownSourceRowsQuery
{
    public function __construct(
        private readonly PartsTotalSubquery $parts,
        private readonly CogsSubqueries $cogs,
        private readonly RefundComponentSubqueries $refunds,
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function rows(string $fromTransactionDate, string $toTransactionDate): Collection
    {
        return $this->baseQuery($fromTransactionDate, $toTransactionDate)
            ->orderBy('notes.transaction_date')
            ->orderBy('notes.id')
            ->orderBy('work_items.line_no')
            ->get([
                'notes.id as note_id',
                'work_items.id as work_item_id',
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

    public function summary(string $fromTransactionDate, string $toTransactionDate): object
    {
        return $this->baseQuery($fromTransactionDate, $toTransactionDate)
            ->selectRaw('COUNT(*) as total_packages')
            ->selectRaw('COALESCE(SUM(work_items.subtotal_rupiah), 0) as package_sold_amount_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(parts_totals.parts_total_rupiah, 0)), 0) as parts_total_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0)), 0) as sparepart_cogs_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(parts_totals.parts_total_rupiah, 0) - (COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0))), 0) as sparepart_margin_rupiah')
            ->selectRaw('COALESCE(SUM(work_item_service_details.service_price_rupiah + COALESCE(work_item_service_details.package_profit_rupiah, 0)), 0) as total_service_component_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(refunded_product_components.refunded_product_component_rupiah, 0)), 0) as refunded_product_component_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(refunded_service_components.refunded_service_component_rupiah, 0)), 0) as refunded_service_component_rupiah')
            ->selectRaw('COALESCE(SUM((COALESCE(parts_totals.parts_total_rupiah, 0) - (COALESCE(issued_cogs.issued_cogs_rupiah, 0) - COALESCE(returned_cogs.returned_cogs_rupiah, 0))) + work_item_service_details.service_price_rupiah + COALESCE(work_item_service_details.package_profit_rupiah, 0)), 0) as total_package_gross_profit_rupiah')
            ->first();
    }

    private function baseQuery(string $fromTransactionDate, string $toTransactionDate): Builder
    {
        return DB::table('work_items')
            ->join('notes', 'notes.id', '=', 'work_items.note_id')
            ->join('work_item_service_details', 'work_item_service_details.work_item_id', '=', 'work_items.id')
            ->leftJoinSub($this->parts->query(), 'parts_totals', static fn ($join) => $join->on('parts_totals.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->cogs->issued(), 'issued_cogs', static fn ($join) => $join->on('issued_cogs.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->cogs->returned(), 'returned_cogs', static fn ($join) => $join->on('returned_cogs.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->refunds->product(), 'refunded_product_components', static fn ($join) => $join->on('refunded_product_components.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->refunds->service(), 'refunded_service_components', static fn ($join) => $join->on('refunded_service_components.work_item_id', '=', 'work_items.id'))
            ->where('work_items.transaction_type', 'service_with_store_stock_part')
            ->where('work_items.status', '<>', 'canceled')
            ->whereBetween('notes.transaction_date', [$fromTransactionDate, $toTransactionDate]);
    }
}
