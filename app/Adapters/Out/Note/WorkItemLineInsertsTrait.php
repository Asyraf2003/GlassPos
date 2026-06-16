<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use DateTimeInterface;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Support\Facades\DB;

trait WorkItemLineInsertsTrait
{
    private function insertExternalPurchaseLines(WorkItem $workItem, DateTimeInterface $now): void
    {
        $lines = $workItem->externalPurchaseLines();

        if ($lines === []) {
            return;
        }

        DB::table('work_item_external_purchase_lines')->insert(
            array_map(
                static fn (ExternalPurchaseLine $line): array => [
                    'id' => $line->id(),
                    'work_item_id' => $workItem->id(),
                    'cost_description' => $line->costDescription(),
                    'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
                    'qty' => $line->qty(),
                    'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $lines,
            )
        );
    }

    private function insertStoreStockLines(WorkItem $workItem, DateTimeInterface $now): void
    {
        $lines = $workItem->storeStockLines();

        if ($lines === []) {
            return;
        }

        DB::table('work_item_store_stock_lines')->insert(
            array_map(
                static fn (StoreStockLine $line): array => [
                    'id' => $line->id(),
                    'work_item_id' => $workItem->id(),
                    'product_id' => $line->productId(),
                    'qty' => $line->qty(),
                    'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $lines,
            )
        );
    }
}
