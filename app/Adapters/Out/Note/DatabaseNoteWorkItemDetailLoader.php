<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteWorkItemDetailLoader
{
    public function loadDetails(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $result = [];

        foreach (DB::table('work_item_service_details')->whereIn('work_item_id', $ids)->get() as $row) {
            $result[(string) $row->work_item_id] = ServiceDetail::rehydrate(
                (string) $row->service_name,
                Money::fromInt((int) $row->service_price_rupiah),
                (string) $row->part_source,
            );
        }

        return $result;
    }

    public function loadExternals(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $result = [];

        foreach (DB::table('work_item_external_purchase_lines')->whereIn('work_item_id', $ids)->orderBy('id')->get() as $row) {
            $result[(string) $row->work_item_id][] = ExternalPurchaseLine::rehydrate(
                (string) $row->id,
                (string) $row->cost_description,
                Money::fromInt((int) $row->unit_cost_rupiah),
                (int) $row->qty,
            );
        }

        return $result;
    }

    public function loadStocks(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $result = [];

        foreach (DB::table('work_item_store_stock_lines')->whereIn('work_item_id', $ids)->orderBy('id')->get() as $row) {
            $result[(string) $row->work_item_id][] = StoreStockLine::rehydrate(
                (string) $row->id,
                (string) $row->product_id,
                (int) $row->qty,
                Money::fromInt((int) $row->line_total_rupiah),
            );
        }

        return $result;
    }
}
