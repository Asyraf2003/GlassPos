<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Application\Inventory\Support\InventoryMovementSourceTypes;
use Illuminate\Support\Facades\DB;

final class InventoryMovementSummaryDatabaseQuery
{
    public static function get(string $fromMutationDate, string $toMutationDate): array
    {
        return DB::table('inventory_movements')
            ->leftJoin('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'inventory_movements.product_id')
            ->leftJoin('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'inventory_movements.product_id')
            ->whereBetween('inventory_movements.tanggal_mutasi', [$fromMutationDate, $toMutationDate])
            ->groupBy(
                'inventory_movements.product_id',
                'products.id',
                'products.kode_barang',
                'products.nama_barang',
                'products.deleted_at',
                'product_inventory.qty_on_hand',
                'product_inventory_costing.avg_cost_rupiah',
                'product_inventory_costing.inventory_value_rupiah',
            )
            ->orderBy('products.nama_barang')
            ->orderBy('inventory_movements.product_id')
            ->get(self::columns())
            ->map(static fn (object $row): array => InventoryMovementSummaryRowMapper::map($row))
            ->all();
    }

    private static function columns(): array
    {
        $supplierReceiptLine = InventoryMovementSourceTypes::supplierReceiptLineSql();
        $saleOutSourceTypes = InventoryMovementSourceTypes::saleOutSqlList();
        $storeStockLineReversal = InventoryMovementSourceTypes::storeStockLineReversalSql();
        $classifiedSourceTypes = InventoryMovementSourceTypes::classifiedForReportingSqlList();

        return [
            'inventory_movements.product_id',
            'products.kode_barang',
            DB::raw("
                CASE
                    WHEN products.id IS NULL THEN CONCAT('[Produk tidak ditemukan: ', inventory_movements.product_id, ']')
                    WHEN products.deleted_at IS NOT NULL THEN CONCAT('[Produk terhapus] ', products.nama_barang)
                    ELSE products.nama_barang
                END as nama_barang
            "),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type = {$supplierReceiptLine} AND inventory_movements.qty_delta > 0 THEN inventory_movements.qty_delta ELSE 0 END), 0) as supply_in_qty"),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type IN ({$saleOutSourceTypes}) AND inventory_movements.qty_delta < 0 THEN ABS(inventory_movements.qty_delta) ELSE 0 END), 0) as sale_out_qty"),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type = {$storeStockLineReversal} AND inventory_movements.qty_delta > 0 THEN inventory_movements.qty_delta ELSE 0 END), 0) as refund_reversal_qty"),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type NOT IN ({$classifiedSourceTypes}) THEN ABS(inventory_movements.qty_delta) ELSE 0 END), 0) as revision_correction_qty"),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type = {$supplierReceiptLine} AND inventory_movements.qty_delta > 0 THEN inventory_movements.qty_delta ELSE 0 END), 0) as qty_in"),
            DB::raw("COALESCE(SUM(CASE WHEN inventory_movements.source_type IN ({$saleOutSourceTypes}) AND inventory_movements.qty_delta < 0 THEN ABS(inventory_movements.qty_delta) ELSE 0 END), 0) as qty_out"),
            DB::raw('COALESCE(SUM(inventory_movements.qty_delta), 0) as net_qty_delta'),
            DB::raw('COALESCE(SUM(CASE WHEN inventory_movements.total_cost_rupiah > 0 THEN inventory_movements.total_cost_rupiah ELSE 0 END), 0) as total_in_cost_rupiah'),
            DB::raw('COALESCE(SUM(CASE WHEN inventory_movements.total_cost_rupiah < 0 THEN ABS(inventory_movements.total_cost_rupiah) ELSE 0 END), 0) as total_out_cost_rupiah'),
            DB::raw('COALESCE(SUM(inventory_movements.total_cost_rupiah), 0) as net_cost_delta_rupiah'),
            DB::raw('COALESCE(product_inventory.qty_on_hand, 0) as current_qty_on_hand'),
            DB::raw('COALESCE(product_inventory_costing.avg_cost_rupiah, 0) as current_avg_cost_rupiah'),
            DB::raw('COALESCE(product_inventory_costing.inventory_value_rupiah, 0) as current_inventory_value_rupiah'),
        ];
    }
}
