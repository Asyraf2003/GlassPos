<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ProductInventoryThresholdBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $productIds = DB::table('products as p')
            ->leftJoin('product_inventory as pi', 'pi.product_id', '=', 'p.id')
            ->leftJoin('product_inventory_costing as pic', 'pic.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->whereNull('p.reorder_point_qty')
            ->whereNull('p.critical_threshold_qty')
            ->where(function ($query): void {
                $query->whereNotNull('pi.product_id')
                    ->orWhereNotNull('pic.product_id');
            })
            ->distinct()
            ->pluck('p.id')
            ->filter(fn ($id): bool => is_string($id) && trim($id) !== '')
            ->values()
            ->all();

        if ($productIds === []) {
            $this->command?->info('ProductInventoryThresholdBackfillSeeder dilewati: tidak ada produk snapshot yang perlu diisi threshold.');

            return;
        }

        DB::table('products')
            ->whereIn('id', $productIds)
            ->update([
                'reorder_point_qty' => 5,
                'critical_threshold_qty' => 3,
            ]);

        $this->command?->info('ProductInventoryThresholdBackfillSeeder selesai: ' . count($productIds) . ' produk snapshot diisi threshold default 5/3.');
    }
}
