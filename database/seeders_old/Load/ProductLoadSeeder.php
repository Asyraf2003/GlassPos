<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

use App\Application\ProductCatalog\UseCases\CreateProductHandler;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProductLoadSeeder extends Seeder
{
    public function run(
        CreateProductHandler $createHandler,
        UpdateProductHandler $updateHandler,
    ): void {
        $this->seedActiveClean($createHandler, $updateHandler, 270);
        $this->seedActiveEdited($createHandler, $updateHandler, 30);
    }

    private function seedActiveClean(
        CreateProductHandler $createHandler,
        UpdateProductHandler $updateHandler,
        int $count,
    ): void {
        for ($i = 1; $i <= $count; $i++) {
            $payload = $this->buildCleanPayload($i);
            $thresholds = $this->thresholdPayload($i);
            $productId = $this->findProductIdByCode($payload['kodeBarang']);

            if ($productId === null) {
                $created = $createHandler->handle(
                    kodeBarang: $payload['kodeBarang'],
                    namaBarang: $payload['namaBarang'],
                    merek: $payload['merek'],
                    ukuran: $payload['ukuran'],
                    hargaJual: $payload['hargaJual'],
                    reorderPointQty: $thresholds['reorderPointQty'],
                    criticalThresholdQty: $thresholds['criticalThresholdQty'],
                );

                if ($created->isFailure()) {
                    Log::warning('ProductLoadSeeder active clean create gagal.', [
                        'message' => $created->message(),
                        'index' => $i,
                        'kode_barang' => $payload['kodeBarang'],
                    ]);
                    continue;
                }

                continue;
            }

            $updated = $updateHandler->handle(
                productId: $productId,
                kodeBarang: $payload['kodeBarang'],
                namaBarang: $payload['namaBarang'],
                merek: $payload['merek'],
                ukuran: $payload['ukuran'],
                hargaJual: $payload['hargaJual'],
                reorderPointQty: $thresholds['reorderPointQty'],
                criticalThresholdQty: $thresholds['criticalThresholdQty'],
            );

            if ($updated->isFailure()) {
                Log::warning('ProductLoadSeeder active clean update gagal.', [
                    'message' => $updated->message(),
                    'product_id' => $productId,
                    'index' => $i,
                    'kode_barang' => $payload['kodeBarang'],
                ]);
            }
        }
    }

    private function seedActiveEdited(
        CreateProductHandler $createHandler,
        UpdateProductHandler $updateHandler,
        int $count,
    ): void {
        for ($i = 1; $i <= $count; $i++) {
            $createPayload = $this->buildEditedCreatePayload($i);
            $updatePayload = $this->buildEditedUpdatePayload($i);
            $thresholds = $this->thresholdPayload($i);

            $productId = $this->resolveEditedProductId(
                createCode: $createPayload['kodeBarang'],
                updateCode: $updatePayload['kodeBarang'],
            );

            if ($productId === null) {
                $created = $createHandler->handle(
                    kodeBarang: $createPayload['kodeBarang'],
                    namaBarang: $createPayload['namaBarang'],
                    merek: $createPayload['merek'],
                    ukuran: $createPayload['ukuran'],
                    hargaJual: $createPayload['hargaJual'],
                    reorderPointQty: $thresholds['reorderPointQty'],
                    criticalThresholdQty: $thresholds['criticalThresholdQty'],
                );

                if ($created->isFailure()) {
                    Log::warning('ProductLoadSeeder active edited create gagal.', [
                        'message' => $created->message(),
                        'index' => $i,
                        'kode_barang' => $createPayload['kodeBarang'],
                    ]);
                    continue;
                }

                $productId = $created->data()['id'] ?? null;
            }

            if (! is_string($productId) || trim($productId) === '') {
                Log::warning('ProductLoadSeeder active edited tidak mendapat product id.', [
                    'index' => $i,
                    'create_payload' => $createPayload,
                    'update_payload' => $updatePayload,
                ]);
                continue;
            }

            $updated = $updateHandler->handle(
                productId: $productId,
                kodeBarang: $updatePayload['kodeBarang'],
                namaBarang: $updatePayload['namaBarang'],
                merek: $updatePayload['merek'],
                ukuran: $updatePayload['ukuran'],
                hargaJual: $updatePayload['hargaJual'],
                reorderPointQty: $thresholds['reorderPointQty'],
                criticalThresholdQty: $thresholds['criticalThresholdQty'],
            );

            if ($updated->isFailure()) {
                Log::warning('ProductLoadSeeder active edited update gagal.', [
                    'message' => $updated->message(),
                    'product_id' => $productId,
                    'index' => $i,
                    'kode_barang' => $updatePayload['kodeBarang'],
                ]);
            }
        }
    }

    /**
     * @return array{reorderPointQty:int,criticalThresholdQty:int}
     */
    private function thresholdPayload(int $index): array
    {
        return match ($index % 3) {
            0 => ['reorderPointQty' => 5, 'criticalThresholdQty' => 2],
            1 => ['reorderPointQty' => 8, 'criticalThresholdQty' => 3],
            default => ['reorderPointQty' => 12, 'criticalThresholdQty' => 5],
        };
    }

    /**
     * @return array{kodeBarang:string,namaBarang:string,merek:string,ukuran:?int,hargaJual:int}
     */
    private function buildCleanPayload(int $index): array
    {
        return [
            'kodeBarang' => sprintf('PRD-V2-ACT-%03d', $index),
            'namaBarang' => $this->buildName($index, false),
            'merek' => $this->brandAt($index),
            'ukuran' => $this->buildSize($index),
            'hargaJual' => 28000 + (($index * 13750) % 420000),
        ];
    }

    /**
     * @return array{kodeBarang:string,namaBarang:string,merek:string,ukuran:?int,hargaJual:int}
     */
    private function buildEditedCreatePayload(int $index): array
    {
        return [
            'kodeBarang' => sprintf('PRD-V2-EDT-%03d', $index),
            'namaBarang' => $this->buildName($index + 270, true) . ' Draft',
            'merek' => $this->brandAt($index + 2),
            'ukuran' => $this->buildSize($index + 270),
            'hargaJual' => 35000 + (($index * 11000) % 360000),
        ];
    }

    /**
     * @return array{kodeBarang:string,namaBarang:string,merek:string,ukuran:?int,hargaJual:int}
     */
    private function buildEditedUpdatePayload(int $index): array
    {
        return [
            'kodeBarang' => sprintf('PRD-V2-EDT-%03d', $index),
            'namaBarang' => $this->buildName($index + 270, true),
            'merek' => $this->brandAt($index + 5),
            'ukuran' => $this->buildSize($index + 271),
            'hargaJual' => 42000 + (($index * 12500) % 390000),
        ];
    }

    private function buildName(int $index, bool $edited): string
    {
        $parts = ['Filter Oli', 'Kampas Rem', 'Busi', 'V Belt', 'Roller', 'Rantai', 'Piston Kit', 'Shockbreaker', 'Ban Luar', 'Ban Dalam', 'Seal Shock', 'Kabel Gas', 'Spion', 'CDI', 'Kanvas Kopling'];
        $vehicles = ['Beat', 'Vario', 'NMAX', 'PCX', 'Mio', 'Aerox', 'Jupiter Z', 'Supra X', 'Scoopy', 'Satria FU', 'Megapro', 'Tiger'];
        $variants = ['Std', 'Plus', 'Pro', 'Street', 'Touring', 'Max', 'Prime', 'Racing', 'X', 'Sport'];

        $part = $parts[$index % count($parts)];
        $vehicle = $vehicles[$index % count($vehicles)];
        $variant = $variants[$index % count($variants)];

        return $edited
            ? sprintf('%s %s %s Rev', $part, $vehicle, $variant)
            : sprintf('%s %s %s', $part, $vehicle, $variant);
    }

    private function buildSize(int $index): ?int
    {
        $sizes = [null, 80, 90, 100, 110, 125, 150, 200, 250, 300, 428];

        return $sizes[$index % count($sizes)];
    }

    private function brandAt(int $index): string
    {
        $brands = ['Federal', 'Astra', 'Nissin', 'NGK', 'Stanley', 'Bando', 'Yamaha', 'YGP', 'DID', 'Mikuni', 'FIM', 'KYB', 'FDR', 'Showa', 'BRT', 'FCC'];

        return $brands[$index % count($brands)];
    }

    private function findProductIdByCode(string $kodeBarang): ?string
    {
        $productId = DB::table('products')
            ->where('kode_barang', trim($kodeBarang))
            ->value('id');

        return is_string($productId) && trim($productId) !== ''
            ? $productId
            : null;
    }

    private function resolveEditedProductId(string $createCode, string $updateCode): ?string
    {
        $createdId = $this->findProductIdByCode($createCode);

        if ($createdId !== null) {
            return $createdId;
        }

        return $this->findProductIdByCode($updateCode);
    }
}
