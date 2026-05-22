<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

use App\Application\ProductCatalog\UseCases\CreateProductHandler;
use App\Application\ProductCatalog\UseCases\SoftDeleteProductHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProductScenarioRecreatedSeeder extends Seeder
{
    public function run(
        CreateProductHandler $createHandler,
        SoftDeleteProductHandler $deleteHandler,
    ): void {
        for ($number = 1; $number <= 4; $number++) {
            $code = 'PRD-RCR-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);

            if ($this->productCodeAlreadySeeded($code)) {
                continue;
            }

            $productId = $this->createProduct($createHandler, $code, $number, 'Original');

            if ($productId === null) {
                continue;
            }

            $deleted = $deleteHandler->handle($productId, 'system-seeder');

            if ($deleted->isFailure()) {
                Log::warning('ProductScenarioRecreatedSeeder delete original gagal.', [
                    'message' => $deleted->message(),
                    'product_id' => $productId,
                ]);
                continue;
            }

            $this->createProduct($createHandler, $code, $number, 'Replacement');
        }
    }

    private function createProduct(
        CreateProductHandler $handler,
        string $code,
        int $number,
        string $suffix,
    ): ?string {
        $thresholds = ProductSeedThresholds::forIndex($number);
        $result = $handler->handle(
            kodeBarang: $code,
            namaBarang: 'Produk Recreated ' . $number . ' ' . $suffix,
            merek: 'Seed',
            ukuran: null,
            hargaJual: 20000 + ($number * 1000),
            reorderPointQty: $thresholds['reorderPointQty'],
            criticalThresholdQty: $thresholds['criticalThresholdQty'],
        );

        if ($result->isFailure()) {
            Log::warning('ProductScenarioRecreatedSeeder create gagal.', [
                'message' => $result->message(),
                'code' => $code,
            ]);

            return null;
        }

        $productId = $result->data()['id'] ?? null;

        return is_string($productId) && trim($productId) !== '' ? $productId : null;
    }

    private function productCodeAlreadySeeded(string $kodeBarang): bool
    {
        return DB::table('products')
            ->where('kode_barang', $kodeBarang)
            ->exists();
    }
}
