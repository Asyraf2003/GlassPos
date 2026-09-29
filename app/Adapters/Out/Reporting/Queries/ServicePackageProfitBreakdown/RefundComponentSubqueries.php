<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class RefundComponentSubqueries
{
    public function product(?string $asOfDate = null): Builder
    {
        return DB::table('refund_component_allocations as allocations')
            ->join('customer_refunds as refunds', 'refunds.id', '=', 'allocations.customer_refund_id')
            ->whereIn('allocations.component_type', [
                'product_only_work_item',
                'service_store_stock_part',
            ])
            ->when($asOfDate !== null, fn (Builder $query) => $query->where('refunds.refunded_at', '<=', $asOfDate))
            ->selectRaw('allocations.work_item_id, SUM(allocations.refunded_amount_rupiah) as refunded_product_component_rupiah')
            ->groupBy('allocations.work_item_id');
    }

    public function service(?string $asOfDate = null): Builder
    {
        return DB::table('refund_component_allocations as allocations')
            ->join('customer_refunds as refunds', 'refunds.id', '=', 'allocations.customer_refund_id')
            ->where('allocations.component_type', 'service_fee')
            ->when($asOfDate !== null, fn (Builder $query) => $query->where('refunds.refunded_at', '<=', $asOfDate))
            ->selectRaw('allocations.work_item_id, SUM(allocations.refunded_amount_rupiah) as refunded_service_component_rupiah')
            ->groupBy('allocations.work_item_id');
    }
}
