<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProductScenarioLegacyIncompleteSeeder extends Seeder
{
    public function run(
        UpdateProductHandler $updateHandler,
    ): void {
        $items = ProductSeedCatalog::all()['legacy_incomplete_history'];

        foreach ($items as $item) {
            $productId = $this->resolveExistingProductId(
                createCode: $item['create']['code'],
                updateCode: $item['update']['code'],
            );

            if ($productId === null) {
                $productId = (string) Str::uuid();

                DB::table('products')->insert([
                    'id' => $productId,
                    'kode_barang' => $item['create']['code'],
                    'nama_barang' => $item['create']['name'],
                    'nama_barang_normalized' => $this->normalize($item['create']['name']),
                    'merek' => $item['create']['brand'],
                    'merek_normalized' => $this->normalize($item['create']['brand']),
                    'ukuran' => $item['create']['size'],
                    'harga_jual' => $item['create']['price'],
                    'deleted_at' => null,
                    'deleted_by_actor_id' => null,
                    'delete_reason' => null,
                ]);
            }

            $updated = $updateHandler->handle(
                productId: $productId,
                kodeBarang: $item['update']['code'],
                namaBarang: $item['update']['name'],
                merek: $item['update']['brand'],
                ukuran: $item['update']['size'],
                hargaJual: $item['update']['price'],
                reorderPointQty: null,
                criticalThresholdQty: null,
            );

            if ($updated->isFailure()) {
                Log::warning('ProductScenarioLegacyIncompleteSeeder update gagal.', [
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

    private function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($normalized);
    }
}
