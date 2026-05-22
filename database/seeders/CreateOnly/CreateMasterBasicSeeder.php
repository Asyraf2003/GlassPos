<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Database\Seeders\CreateOnly\Support\CreateOnlyMasterSeeder;

final class CreateMasterBasicSeeder extends CreateOnlyMasterSeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $this->seedSuppliers('basic', 3);
        $this->seedProducts('basic', 10);
        $this->seedEmployees('basic', 3);
        $this->seedExpenseCategories('basic');
    }
}
