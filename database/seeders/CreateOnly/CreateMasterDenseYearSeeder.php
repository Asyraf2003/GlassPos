<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Database\Seeders\CreateOnly\Support\CreateOnlyMasterSeeder;

final class CreateMasterDenseYearSeeder extends CreateOnlyMasterSeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $this->seedSuppliers('year', 60);
        $this->seedProducts('year', 1200);
        $this->seedEmployees('year', 40);
        $this->seedExpenseCategories('year');
    }
}
