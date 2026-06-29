<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Application\Inventory\Support\InventoryMovementSourceTypes;
use Illuminate\Support\Facades\DB;

final class InventoryMovementReconciliationDatabaseQuery
{
    public static function get(string $fromMutationDate, string $toMutationDate): array
    {
        $supplierReceiptLine = InventoryMovementSourceTypes::supplierReceiptLineSql();
        $saleOutSourceTypes = InventoryMovementSourceTypes::saleOutSqlList();
        $storeStockLineReversal = InventoryMovementSourceTypes::storeStockLineReversalSql();
        $classifiedSourceTypes = InventoryMovementSourceTypes::classifiedForReportingSqlList();

        $totals = DB::table('inventory_movements')
            ->whereBetween('tanggal_mutasi', [$fromMutationDate, $toMutationDate])
            ->selectRaw(
                "COUNT(DISTINCT product_id) as total_rows, " .
                "COALESCE(SUM(CASE WHEN source_type = {$supplierReceiptLine} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as supply_in_qty, " .
                "COALESCE(SUM(CASE WHEN source_type IN ({$saleOutSourceTypes}) AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as sale_out_qty, " .
                "COALESCE(SUM(CASE WHEN source_type = {$storeStockLineReversal} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as refund_reversal_qty, " .
                "COALESCE(SUM(CASE WHEN source_type NOT IN ({$classifiedSourceTypes}) THEN ABS(qty_delta) ELSE 0 END), 0) as revision_correction_qty, " .
                "COALESCE(SUM(CASE WHEN source_type = {$supplierReceiptLine} AND qty_delta > 0 THEN qty_delta ELSE 0 END), 0) as qty_in, " .
                "COALESCE(SUM(CASE WHEN source_type IN ({$saleOutSourceTypes}) AND qty_delta < 0 THEN ABS(qty_delta) ELSE 0 END), 0) as qty_out, " .
                "COALESCE(SUM(qty_delta), 0) as net_qty_delta, " .
                "COALESCE(SUM(CASE WHEN total_cost_rupiah > 0 THEN total_cost_rupiah ELSE 0 END), 0) as total_in_cost_rupiah, " .
                "COALESCE(SUM(CASE WHEN total_cost_rupiah < 0 THEN ABS(total_cost_rupiah) ELSE 0 END), 0) as total_out_cost_rupiah, " .
                "COALESCE(SUM(total_cost_rupiah), 0) as net_cost_delta_rupiah"
            )
            ->first();

        return [
            'total_rows' => (int) ($totals->total_rows ?? 0),
            'supply_in_qty' => (int) ($totals->supply_in_qty ?? 0),
            'sale_out_qty' => (int) ($totals->sale_out_qty ?? 0),
            'refund_reversal_qty' => (int) ($totals->refund_reversal_qty ?? 0),
            'revision_correction_qty' => (int) ($totals->revision_correction_qty ?? 0),
            'qty_in' => (int) ($totals->qty_in ?? 0),
            'qty_out' => (int) ($totals->qty_out ?? 0),
            'net_qty_delta' => (int) ($totals->net_qty_delta ?? 0),
            'total_in_cost_rupiah' => (int) ($totals->total_in_cost_rupiah ?? 0),
            'total_out_cost_rupiah' => (int) ($totals->total_out_cost_rupiah ?? 0),
            'net_cost_delta_rupiah' => (int) ($totals->net_cost_delta_rupiah ?? 0),
        ];
    }
}
