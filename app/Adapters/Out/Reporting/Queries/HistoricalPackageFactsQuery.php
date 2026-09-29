<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Facades\DB;

final class HistoricalPackageFactsQuery
{
    /** @param list<string> $stockLineIds @return array<string, int> */
    public function cogsByStockLine(array $stockLineIds, string $asOfDate): array
    {
        if ($stockLineIds === []) {
            return [];
        }
        $rows = DB::table('inventory_movements')
            ->whereIn('source_id', $stockLineIds)
            ->whereIn('source_type', [
                'work_item_store_stock_line',
                'work_item_store_stock_line_reversal',
                'transaction_workspace_updated',
            ])
            ->where('tanggal_mutasi', '<=', $asOfDate)
            ->get(['source_id', 'movement_type', 'total_cost_rupiah']);
        $totals = [];
        foreach ($rows as $row) {
            $id = (string) $row->source_id;
            $cost = abs((int) $row->total_cost_rupiah);
            $totals[$id] = ($totals[$id] ?? 0)
                + ((string) $row->movement_type === 'stock_out' ? $cost : -$cost);
        }
        return $totals;
    }

    /** @param list<string> $workItemIds @return array<string, array{product:int,service:int}> */
    public function refundsByWorkItem(array $workItemIds, string $asOfDate): array
    {
        if ($workItemIds === []) {
            return [];
        }
        $rows = DB::table('refund_component_allocations as allocations')
            ->join('customer_refunds as refunds', 'refunds.id', '=', 'allocations.customer_refund_id')
            ->whereIn('allocations.work_item_id', $workItemIds)
            ->where('refunds.refunded_at', '<=', $asOfDate)
            ->get(['allocations.work_item_id', 'allocations.component_type', 'allocations.refunded_amount_rupiah']);
        $totals = [];
        foreach ($rows as $row) {
            $id = (string) $row->work_item_id;
            $totals[$id] ??= ['product' => 0, 'service' => 0];
            $bucket = (string) $row->component_type === 'service_fee' ? 'service' : 'product';
            $totals[$id][$bucket] += (int) $row->refunded_amount_rupiah;
        }
        return $totals;
    }
}
