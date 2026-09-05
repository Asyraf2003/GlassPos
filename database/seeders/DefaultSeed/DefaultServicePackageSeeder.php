<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\ServiceProductTemplate\UseCases\CreateServiceProductTemplateHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Database\Seeders\DefaultSeed\Support\SeedResultGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultServicePackageSeeder extends Seeder
{
    public function run(CreateServiceProductTemplateHandler $packages): void
    {
        $products = DB::table('products')
            ->where('kode_barang', 'like', 'DEF-SP-%')
            ->orderBy('kode_barang')
            ->get(['id'])
            ->values();
        $services = DB::table('service_catalog_items')
            ->where('name', 'like', 'Default Service %')
            ->orderBy('name')
            ->get(['id'])
            ->values();

        if ($products->count() !== 1000 || $services->count() !== 50) {
            throw new RuntimeException('Default product/service prerequisites are incomplete.');
        }

        $actorId = DefaultSeedActor::adminId();

        foreach ($services as $index => $service) {
            $primary = (string) $products[($index * 17) % $products->count()]->id;

            if (DB::table('service_product_templates')
                ->where('product_id', $primary)
                ->where('service_catalog_item_id', (string) $service->id)
                ->where('is_active', true)
                ->exists()) {
                continue;
            }

            $lineCount = 1 + ($index % 3);
            $lines = [];

            for ($line = 0; $line < $lineCount; $line++) {
                $productIndex = (($index * 17) + ($line * 131)) % $products->count();
                $lines[] = [
                    'product_id' => (string) $products[$productIndex]->id,
                    'qty' => 1,
                    'sort_order' => $line,
                ];
            }

            SeedResultGuard::data($packages->handle(
                $lines,
                (string) $service->id,
                $actorId,
                'seed_default',
            ), 'create default service package '.($index + 1));
        }
    }
}
