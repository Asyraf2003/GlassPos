<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Application\ProductCatalog\UseCases\CreateProductHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Database\Seeders\DefaultSeed\Support\SeedResultGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DefaultProductSeeder extends Seeder
{
    private const PARTS = [
        'Kampas Rem Depan', 'Kampas Rem Belakang', 'Busi', 'Filter Udara', 'Filter Oli',
        'Rantai', 'Gear Depan', 'Gear Belakang', 'Bearing Roda', 'Seal Shock',
        'Kabel Gas', 'Kabel Kopling', 'Bohlam Lampu', 'Relay Starter', 'Bendik Starter',
        'CDI ECU', 'Koil', 'Piston Kit', 'Ring Piston', 'Gasket Set',
    ];

    private const BRANDS = [
        'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Federal',
        'Aspira', 'Daytona', 'TDR', 'BRT', 'NGK',
    ];

    private const SIZES = [50, 60, 70, 80, 90];

    public function run(CreateProductHandler $products, ProductChangeContext $context): void
    {
        $context->set(DefaultSeedActor::adminId(), 'admin', 'seed_default', 'default six-month seed');

        try {
            foreach (self::PARTS as $partIndex => $part) {
                foreach (self::BRANDS as $brandIndex => $brand) {
                    foreach (self::SIZES as $variantIndex => $size) {
                        $code = sprintf('DEF-SP-%02d-%02d-%02d', $partIndex + 1, $brandIndex + 1, $variantIndex + 1);

                        if (DB::table('products')->where('kode_barang', $code)->exists()) {
                            continue;
                        }

                        SeedResultGuard::data($products->handle(
                            $code,
                            sprintf('%s %s Varian %d', $part, $brand, $variantIndex + 1),
                            $brand,
                            $size,
                            12000 + ($partIndex * 9000) + ($brandIndex * 2500) + ($variantIndex * 1500),
                            10 + ($variantIndex * 2),
                            3 + $variantIndex,
                        ), 'create product '.$code);
                    }
                }
            }
        } finally {
            $context->clear();
        }
    }
}
