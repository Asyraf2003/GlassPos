<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Application\Inventory\Services\InventoryCostingProjectionBuilder;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final class InventoryAsOfSnapshotQuery
{
    public function __construct(private readonly InventoryCostingProjectionBuilder $costing) {}

    public function rows(string $asOf): array
    {
        $records = DB::table('inventory_movements')->where('tanggal_mutasi', '<=', $asOf)
            ->orderBy('tanggal_mutasi')->orderBy('id')->get();
        $movements = [];
        $quantities = [];
        $values = [];
        foreach ($records as $row) {
            $id = (string) $row->product_id;
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $row->qty_delta;
            $values[$id] = ($values[$id] ?? 0) + (int) $row->total_cost_rupiah;
            $movements[] = InventoryMovement::rehydrate((string) $row->id, $id,
                (string) $row->movement_type, (string) $row->source_type, (string) $row->source_id,
                new \DateTimeImmutable((string) $row->tanggal_mutasi), (int) $row->qty_delta,
                Money::fromInt((int) $row->unit_cost_rupiah), Money::fromInt((int) $row->total_cost_rupiah));
        }
        $costs = [];
        foreach ($this->costing->build($movements) as $cost) {
            $costs[$cost->productId()] = $cost;
        }

        return DB::table('products')->whereIn('id', array_keys($quantities))
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $asOf.' 23:59:59'))
            ->orderBy('id')->get()->map(static function (object $row) use ($quantities, $values, $costs): array {
                $id = (string) $row->id;
                $qty = $quantities[$id];
                $value = $values[$id];
                $average = isset($costs[$id]) ? $costs[$id]->avgCostRupiah()->amount() : 0;
                $row->product_id = $id;
                $row->current_qty_on_hand = $qty;
                $row->current_avg_cost_rupiah = $average;
                $row->current_inventory_value_rupiah = $value;
                $row->current_inventory_value_by_average_rupiah = $average * $qty;
                $row->current_rounding_residual_rupiah = $value - $average * $qty;
                $row->ledger_qty_on_hand = $qty;
                $row->ledger_inventory_value_rupiah = $value;
                $row->ledger_qty_diff = 0;
                $row->ledger_value_diff_rupiah = 0;

                return InventoryCurrentSnapshotRowMapper::map($row);
            })->all();
    }
}
