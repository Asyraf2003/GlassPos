<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

use App\Application\ProductCatalog\UseCases\CreateProductHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProductScenarioActiveBasicSeeder extends Seeder
{
    public function run(CreateProductHandler $handler): void
    {
        $items = ProductSeedCatalog::all()['active_basic'];

        foreach ($items as $index => $item) {
            $code = trim($item['code']);

            if ($this->productCodeAlreadySeeded($code)) {
                continue;
            }

            $thresholds = ProductSeedThresholds::forIndex($index + 1);

            $result = $handler->handle(
                kodeBarang: $code,
                namaBarang: $item['name'],
                merek: $item['brand'],
                ukuran: $item['size'],
                hargaJual: $item['price'],
                reorderPointQty: $thresholds['reorderPointQty'],
                criticalThresholdQty: $thresholds['criticalThresholdQty'],
            );

            if ($result->isFailure()) {
                Log::warning('ProductScenarioActiveBasicSeeder gagal.', [
                    'message' => $result->message(),
                    'item' => $item,
                ]);
            }
        }
    }

    private function productCodeAlreadySeeded(string $kodeBarang): bool
    {
        return DB::table('products')
            ->where('kode_barang', $kodeBarang)
            ->exists();
    }
}
