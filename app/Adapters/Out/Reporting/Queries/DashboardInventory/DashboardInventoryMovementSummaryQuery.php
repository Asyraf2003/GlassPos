<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardInventory;

use App\Application\Inventory\Support\InventoryMovementSourceTypes;
use Illuminate\Support\Facades\DB;

final class DashboardInventoryMovementSummaryQuery
{
    /**
     * @return array<string, int>
     */
    public function get(string $fromMutationDate, string $toMutationDate): array
    {
        $supplierReceiptLine = InventoryMovementSourceTypes::supplierReceiptLineSql();
        $saleOutSourceTypes = InventoryMovementSourceTypes::saleOutSqlList();
        $storeStockLineReversal = InventoryMovementSourceTypes::storeStockLineReversalSql();
        $classifiedSourceTypes = InventoryMovementSourceTypes::classifiedForReportingSqlList();

        $row = DB::table('inventory_movements')
            ->whereBetween('tanggal_mutasi', [$fromMutationDate, $toMutationDate])
            ->selectRaw('COUNT(DISTINCT product_id) as movement_product_rows')
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = {$supplierReceiptLine} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_supply_in_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type IN ({$saleOutSourceTypes}) AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as period_sale_out_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = {$storeStockLineReversal} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_refund_reversal_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type NOT IN ({$classifiedSourceTypes}) THEN ABS(qty_delta) ELSE 0 END), 0) as period_revision_correction_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = {$supplierReceiptLine} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_qty_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type IN ({$saleOutSourceTypes}) AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as period_qty_out")
            ->selectRaw('COALESCE(SUM(qty_delta), 0) as period_net_qty_delta')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_cost_rupiah > 0 THEN total_cost_rupiah ELSE 0 END), 0) as period_total_in_cost_rupiah')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_cost_rupiah < 0 THEN ABS(total_cost_rupiah) ELSE 0 END), 0) as period_total_out_cost_rupiah')
            ->selectRaw('COALESCE(SUM(total_cost_rupiah), 0) as period_net_cost_delta_rupiah')
            ->first();

        return [
            'movement_product_rows' => (int) ($row->movement_product_rows ?? 0),
            'period_supply_in_qty' => (int) ($row->period_supply_in_qty ?? 0),
            'period_sale_out_qty' => (int) ($row->period_sale_out_qty ?? 0),
            'period_refund_reversal_qty' => (int) ($row->period_refund_reversal_qty ?? 0),
            'period_revision_correction_qty' => (int) ($row->period_revision_correction_qty ?? 0),
            'period_qty_in' => (int) ($row->period_qty_in ?? 0),
            'period_qty_out' => (int) ($row->period_qty_out ?? 0),
            'period_net_qty_delta' => (int) ($row->period_net_qty_delta ?? 0),
            'period_total_in_cost_rupiah' => (int) ($row->period_total_in_cost_rupiah ?? 0),
            'period_total_out_cost_rupiah' => (int) ($row->period_total_out_cost_rupiah ?? 0),
            'period_net_cost_delta_rupiah' => (int) ($row->period_net_cost_delta_rupiah ?? 0),
        ];
    }
}
