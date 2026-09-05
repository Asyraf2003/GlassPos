<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\DefaultSeed\DefaultSixMonthSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DefaultSixMonthSeeder::class);
    }
}
