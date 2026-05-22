<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Database\Seeders\CreateOnly\Support\CreateOnlyMasterSeeder;

final class CreateMasterDenseWeekSeeder extends CreateOnlyMasterSeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $this->seedSuppliers('week', 15);
        $this->seedProducts('week', 150);
        $this->seedEmployees('week', 10);
        $this->seedExpenseCategories('week');
    }
}
