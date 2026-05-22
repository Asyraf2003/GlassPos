<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Expense\ExpenseBaselineSeeder;
use Database\Seeders\Transaction\CustomerCorrectionBaselineSeeder;
use Database\Seeders\Transaction\CustomerPaymentBaselineSeeder;
use Database\Seeders\Transaction\CustomerRefundBaselineSeeder;
use Database\Seeders\Transaction\CustomerTransactionBaselineSeeder;
use Illuminate\Database\Seeder;

final class SeedLevel2Seeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            EmployeeFinanceBaselineSeeder::class,
            SupplierInvoiceBaselineSeeder::class,
            SupplierInvoiceScenarioSeeder::class,
            SupplierInvoiceVoidedScenarioSeeder::class,
            ExpenseBaselineSeeder::class,
            CustomerTransactionBaselineSeeder::class,
            ProductInventoryThresholdBackfillSeeder::class,
            CustomerPaymentBaselineSeeder::class,
            CustomerRefundBaselineSeeder::class,
            CustomerCorrectionBaselineSeeder::class,
        ]);
    }
}
