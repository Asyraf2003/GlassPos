<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use Illuminate\Support\Facades\DB;

final class InventoryStockValueSummaryDatabaseQuery
{
    /**
     * @return array<string, int>
     */
    public static function get(string $fromMutationDate, string $toMutationDate): array
    {
        $movementLedger = DB::table('inventory_movements')
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(qty_delta), 0) as ledger_qty_on_hand')
            ->selectRaw('COALESCE(SUM(total_cost_rupiah), 0) as ledger_inventory_value_rupiah')
            ->groupBy('product_id');

        $snapshot = DB::table('products')
            ->whereNull('products.deleted_at')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->leftJoin('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->leftJoinSub($movementLedger, 'inventory_movement_ledger', static function ($join): void {
                $join->on('inventory_movement_ledger.product_id', '=', 'products.id');
            })
            ->where(static function ($query): void {
                $query
                    ->whereNotNull('product_inventory.product_id')
                    ->orWhereNotNull('product_inventory_costing.product_id')
                    ->orWhereNotNull('inventory_movement_ledger.product_id');
            })
            ->selectRaw('COUNT(*) as snapshot_product_rows')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory.qty_on_hand, 0)), 0) as total_qty_on_hand')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory_costing.inventory_value_rupiah, 0)), 0) as total_inventory_value_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory_costing.avg_cost_rupiah, 0) * COALESCE(product_inventory.qty_on_hand, 0)), 0) as total_inventory_value_by_average_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory_costing.inventory_value_rupiah, 0) - (COALESCE(product_inventory_costing.avg_cost_rupiah, 0) * COALESCE(product_inventory.qty_on_hand, 0))), 0) as total_rounding_residual_rupiah')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory.qty_on_hand, 0) - COALESCE(inventory_movement_ledger.ledger_qty_on_hand, 0)), 0) as total_ledger_qty_diff')
            ->selectRaw('COALESCE(SUM(COALESCE(product_inventory_costing.inventory_value_rupiah, 0) - COALESCE(inventory_movement_ledger.ledger_inventory_value_rupiah, 0)), 0) as total_ledger_value_diff_rupiah')
            ->selectRaw("COALESCE(SUM(CASE WHEN products.reorder_point_qty IS NULL OR products.critical_threshold_qty IS NULL THEN 1 ELSE 0 END), 0) as stock_unconfigured_product_rows")
            ->selectRaw("COALESCE(SUM(CASE WHEN products.reorder_point_qty IS NOT NULL AND products.critical_threshold_qty IS NOT NULL AND COALESCE(product_inventory.qty_on_hand, 0) <= products.critical_threshold_qty THEN 1 ELSE 0 END), 0) as stock_critical_product_rows")
            ->selectRaw("COALESCE(SUM(CASE WHEN products.reorder_point_qty IS NOT NULL AND products.critical_threshold_qty IS NOT NULL AND COALESCE(product_inventory.qty_on_hand, 0) > products.critical_threshold_qty AND COALESCE(product_inventory.qty_on_hand, 0) <= products.reorder_point_qty THEN 1 ELSE 0 END), 0) as stock_low_product_rows")
            ->selectRaw("COALESCE(SUM(CASE WHEN products.reorder_point_qty IS NOT NULL AND products.critical_threshold_qty IS NOT NULL AND COALESCE(product_inventory.qty_on_hand, 0) > products.reorder_point_qty THEN 1 ELSE 0 END), 0) as stock_safe_product_rows")
            ->first();

        $movement = DB::table('inventory_movements')
            ->whereBetween('tanggal_mutasi', [$fromMutationDate, $toMutationDate])
            ->selectRaw('COUNT(DISTINCT product_id) as movement_product_rows')
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = 'supplier_receipt_line' AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_supply_in_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type IN ('work_item_store_stock_line', 'note', 'customer_transaction_line') AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as period_sale_out_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = 'work_item_store_stock_line_reversal' AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_refund_reversal_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type NOT IN ('supplier_receipt_line', 'work_item_store_stock_line', 'note', 'customer_transaction_line', 'work_item_store_stock_line_reversal') THEN ABS(qty_delta) ELSE 0 END), 0) as period_revision_correction_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type = 'supplier_receipt_line' AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as period_qty_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN source_type IN ('work_item_store_stock_line', 'note', 'customer_transaction_line') AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as period_qty_out")
            ->selectRaw('COALESCE(SUM(qty_delta), 0) as period_net_qty_delta')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_cost_rupiah > 0 THEN total_cost_rupiah ELSE 0 END), 0) as period_total_in_cost_rupiah')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_cost_rupiah < 0 THEN ABS(total_cost_rupiah) ELSE 0 END), 0) as period_total_out_cost_rupiah')
            ->selectRaw('COALESCE(SUM(total_cost_rupiah), 0) as period_net_cost_delta_rupiah')
            ->first();

        return self::integerMap((array) $snapshot) + self::integerMap((array) $movement);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, int>
     */
    private static function integerMap(array $row): array
    {
        return array_map(static fn (mixed $value): int => (int) $value, $row);
    }
}
