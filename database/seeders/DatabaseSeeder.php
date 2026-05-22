<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\CreateOnly\CreateMasterBasicSeeder;
use Database\Seeders\CreateOnly\CreateUserSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CreateUserSeeder::class,
            CreateMasterBasicSeeder::class,
        ]);
    }
}
