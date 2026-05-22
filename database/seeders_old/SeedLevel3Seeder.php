<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Load\CustomerCorrectionLoadSeeder;
use Database\Seeders\Load\CustomerPaymentLoadSeeder;
use Database\Seeders\Load\CustomerRefundLoadSeeder;
use Database\Seeders\Load\CustomerTransactionLoadSeeder;
use Database\Seeders\Load\ExpenseLoadSeeder;
use Database\Seeders\Load\ProductLoadSeeder;
use Database\Seeders\Load\ProcurementLoadSeeder;
use Illuminate\Database\Seeder;

final class SeedLevel3Seeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            ProductLoadSeeder::class,
            SupplierSeeder::class,
            EmployeeFinanceBaselineSeeder::class,
            ProcurementLoadSeeder::class,
            ProductInventoryThresholdBackfillSeeder::class,
            ExpenseLoadSeeder::class,
            CustomerTransactionLoadSeeder::class,
            CustomerPaymentLoadSeeder::class,
            CustomerRefundLoadSeeder::class,
            CustomerCorrectionLoadSeeder::class,
        ]);
    }
}
