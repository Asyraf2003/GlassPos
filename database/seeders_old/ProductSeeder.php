<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Product\ProductScenarioActiveBasicSeeder;
use Database\Seeders\Product\ProductScenarioEditedSeeder;
use Database\Seeders\Product\ProductScenarioLegacyIncompleteSeeder;
use Database\Seeders\Product\ProductScenarioRecreatedSeeder;
use Database\Seeders\Product\ProductScenarioSoftDeletedSeeder;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductScenarioActiveBasicSeeder::class,
            ProductScenarioEditedSeeder::class,
            ProductScenarioSoftDeletedSeeder::class,
            ProductScenarioRecreatedSeeder::class,
            ProductScenarioLegacyIncompleteSeeder::class,
        ]);
    }
}
