<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface InventoryMovementReportingSourceReaderPort
{
    /**
     * @return list<array{
     *   product_id:string,
     *   kode_barang:?string,
     *   nama_barang:string,
     *   qty_in:int,
     *   qty_out:int,
     *   net_qty_delta:int,
     *   total_in_cost_rupiah:int,
     *   total_out_cost_rupiah:int,
     *   net_cost_delta_rupiah:int,
     *   current_qty_on_hand:int,
     *   current_avg_cost_rupiah:int,
     *   current_inventory_value_rupiah:int
     * }>
     */
    public function getInventoryMovementSummaryRows(
        string $fromMutationDate,
        string $toMutationDate,
    ): array;

    /**
     * @return array{
     *   total_rows:int,
     *   qty_in:int,
     *   qty_out:int,
     *   net_qty_delta:int,
     *   total_in_cost_rupiah:int,
     *   total_out_cost_rupiah:int,
     *   net_cost_delta_rupiah:int
     * }
     */
    public function getInventoryMovementSummaryReconciliation(
        string $fromMutationDate,
        string $toMutationDate,
    ): array;

    /**
     * @return list<array{
     *   product_id:string,
     *   kode_barang:?string,
     *   nama_barang:string,
     *   merek:string,
     *   ukuran:?int,
     *   current_qty_on_hand:int,
     *   current_avg_cost_rupiah:int,
     *   current_inventory_value_rupiah:int
     * }>
     */
    public function getInventoryCurrentSnapshotRows(): array;

    /**
     * @return array<string, int>
     */
    public function getInventoryStockValueSummary(
        string $fromMutationDate,
        string $toMutationDate,
    ): array;
}
