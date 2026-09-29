<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CogsSubqueries
{
    public function issued(?string $asOfDate = null): Builder
    {
        return DB::table('work_item_store_stock_lines')
            ->join('inventory_movements', static function ($join): void {
                $join->on('inventory_movements.source_id', '=', 'work_item_store_stock_lines.id')
                    ->where('inventory_movements.source_type', '=', 'work_item_store_stock_line')
                    ->where('inventory_movements.movement_type', '=', 'stock_out');
            })
            ->when($asOfDate !== null, fn (Builder $query) => $query->where('inventory_movements.tanggal_mutasi', '<=', $asOfDate))
            ->selectRaw('work_item_store_stock_lines.work_item_id, SUM(ABS(inventory_movements.total_cost_rupiah)) as issued_cogs_rupiah')
            ->groupBy('work_item_store_stock_lines.work_item_id');
    }

    public function returned(?string $asOfDate = null): Builder
    {
        return DB::table('work_item_store_stock_lines')
            ->join('inventory_movements', static function ($join): void {
                $join->on('inventory_movements.source_id', '=', 'work_item_store_stock_lines.id')
                    ->where('inventory_movements.source_type', '=', 'work_item_store_stock_line_reversal')
                    ->where('inventory_movements.movement_type', '=', 'stock_in');
            })
            ->when($asOfDate !== null, fn (Builder $query) => $query->where('inventory_movements.tanggal_mutasi', '<=', $asOfDate))
            ->selectRaw('work_item_store_stock_lines.work_item_id, SUM(inventory_movements.total_cost_rupiah) as returned_cogs_rupiah')
            ->groupBy('work_item_store_stock_lines.work_item_id');
    }
}
