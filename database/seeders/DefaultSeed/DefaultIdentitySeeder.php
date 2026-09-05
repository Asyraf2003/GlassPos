<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\IdentityAccess\UseCases\CreateUserWithAccessHandler;
use Database\Seeders\DefaultSeed\Support\SeedResultGuard;
use Illuminate\Database\Seeder;

final class DefaultIdentitySeeder extends Seeder
{
    public function run(CreateUserWithAccessHandler $users): void
    {
        $password = implode('', range(1, 8));
        $admin = SeedResultGuard::data($users->handle(
            'Admin Demo',
            'admin@gmail.com',
            $password,
            'admin',
            null,
            'seed_default',
        ), 'create default admin');

        $adminId = is_array($admin) ? (string) ($admin['id'] ?? '') : '';

        SeedResultGuard::data($users->handle(
            'Kasir Demo',
            'kasir@gmail.com',
            $password,
            'kasir',
            $adminId !== '' ? $adminId : null,
            'seed_default',
        ), 'create default cashier');
    }
}
