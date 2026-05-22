<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

use App\Application\ProductCatalog\UseCases\CreateProductHandler;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProductScenarioEditedSeeder extends Seeder
{
    public function run(
        CreateProductHandler $createHandler,
        UpdateProductHandler $updateHandler,
    ): void {
        $items = ProductSeedCatalog::all()['active_edited'];

        foreach ($items as $index => $item) {
            $thresholds = ProductSeedThresholds::forIndex($index + 1);

            $productId = $this->resolveExistingProductId(
                createCode: $item['create']['code'],
                updateCode: $item['update']['code'],
            );

            if ($productId === null) {
                $created = $createHandler->handle(
                    kodeBarang: $item['create']['code'],
                    namaBarang: $item['create']['name'],
                    merek: $item['create']['brand'],
                    ukuran: $item['create']['size'],
                    hargaJual: $item['create']['price'],
                    reorderPointQty: $thresholds['reorderPointQty'],
                    criticalThresholdQty: $thresholds['criticalThresholdQty'],
                );

                if ($created->isFailure()) {
                    Log::warning('ProductScenarioEditedSeeder create gagal.', [
                        'message' => $created->message(),
                        'item' => $item,
                    ]);
                    continue;
                }

                $productId = $created->data()['id'] ?? null;
            }

            if (! is_string($productId) || trim($productId) === '') {
                Log::warning('ProductScenarioEditedSeeder tidak mendapat product id sebelum update.', [
                    'item' => $item,
                    'resolved_product_id' => $productId,
                ]);
                continue;
            }

            $updated = $updateHandler->handle(
                productId: $productId,
                kodeBarang: $item['update']['code'],
                namaBarang: $item['update']['name'],
                merek: $item['update']['brand'],
                ukuran: $item['update']['size'],
                hargaJual: $item['update']['price'],
                reorderPointQty: $thresholds['reorderPointQty'],
                criticalThresholdQty: $thresholds['criticalThresholdQty'],
            );

            if ($updated->isFailure()) {
                Log::warning('ProductScenarioEditedSeeder update gagal.', [
                    'message' => $updated->message(),
                    'product_id' => $productId,
                    'item' => $item,
                ]);
            }
        }
    }

    private function resolveExistingProductId(string $createCode, string $updateCode): ?string
    {
        $existingId = DB::table('products')
            ->where('kode_barang', trim($createCode))
            ->value('id');

        if (is_string($existingId) && trim($existingId) !== '') {
            return $existingId;
        }

        $updatedId = DB::table('products')
            ->where('kode_barang', trim($updateCode))
            ->value('id');

        if (is_string($updatedId) && trim($updatedId) !== '') {
            return $updatedId;
        }

        return null;
    }
}
