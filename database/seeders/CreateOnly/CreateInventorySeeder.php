<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateInventorySeeder extends Seeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $products = DB::table('products')
            ->select(['id', 'harga_jual'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($products as $index => $product) {
            $lineNo = $index + 1;
            $productId = (string) $product->id;
            $qty = 20 + ($lineNo % 30);
            $unitCost = $this->estimateUnitCost((int) $product->harga_jual);
            $totalCost = $qty * $unitCost;

            $movementId = sprintf('inv-opening-%03d', $lineNo);
            $sourceId = sprintf('opening-stock-%03d', $lineNo);

            $this->createOnly('inventory_movements', 'id', $movementId, [
                'id' => $movementId,
                'product_id' => $productId,
                'movement_type' => 'stock_in',
                'source_type' => 'opening_stock_seed',
                'source_id' => $sourceId,
                'tanggal_mutasi' => now()->toDateString(),
                'qty_delta' => $qty,
                'unit_cost_rupiah' => $unitCost,
                'total_cost_rupiah' => $totalCost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createOnly('product_inventory', 'product_id', $productId, [
                'product_id' => $productId,
                'qty_on_hand' => $qty,
            ]);

            $this->createOnly('product_inventory_costing', 'product_id', $productId, [
                'product_id' => $productId,
                'avg_cost_rupiah' => $unitCost,
                'inventory_value_rupiah' => $totalCost,
            ]);
        }
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(self::class . ' is only allowed in local/testing environments.');
        }
    }

    private function createOnly(string $table, string $key, mixed $value, array $row): bool
    {
        if (DB::table($table)->where($key, '=', $value)->exists()) {
            return false;
        }

        DB::table($table)->insert($this->filterExistingColumns($table, $row));

        return true;
    }

    private function filterExistingColumns(string $table, array $row): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($row, $columns);
    }

    private function estimateUnitCost(int $hargaJual): int
    {
        return max(1, (int) floor($hargaJual * 0.7));
    }
}
