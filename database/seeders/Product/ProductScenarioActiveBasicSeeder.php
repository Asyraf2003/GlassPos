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
        for ($number = 1; $number <= 20; $number++) {
            $code = 'PRD-ACT-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);

            if ($this->productCodeAlreadySeeded($code)) {
                continue;
            }

            $thresholds = ProductSeedThresholds::forIndex($number);
            $result = $handler->handle(
                kodeBarang: $code,
                namaBarang: 'Produk Aktif ' . $number,
                merek: 'Seed',
                ukuran: null,
                hargaJual: 10000 + ($number * 1000),
                reorderPointQty: $thresholds['reorderPointQty'],
                criticalThresholdQty: $thresholds['criticalThresholdQty'],
            );

            if ($result->isFailure()) {
                Log::warning('ProductScenarioActiveBasicSeeder gagal.', [
                    'message' => $result->message(),
                    'code' => $code,
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
