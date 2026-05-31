<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Illuminate\Support\Facades\Hash;

final class CreateUserSeeder extends CreateOnlySeeder
{
    private const DEFAULT_LOCAL_PASSWORD = '12345678';

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $adminId = $this->createOnlyReturningId(
            table: 'users',
            lookupKey: 'email',
            lookupValue: 'admin@gmail.com',
            row: [
                'name' => 'Admin Demo',
                'email' => 'admin@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make(self::DEFAULT_LOCAL_PASSWORD),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $kasirId = $this->createOnlyReturningId(
            table: 'users',
            lookupKey: 'email',
            lookupValue: 'kasir@gmail.com',
            row: [
                'name' => 'Kasir Demo',
                'email' => 'kasir@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make(self::DEFAULT_LOCAL_PASSWORD),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->createOnly('actor_accesses', 'actor_id', (string) $adminId, [
            'actor_id' => (string) $adminId,
            'role' => Role::ADMIN,
        ]);

        $this->createOnly('actor_accesses', 'actor_id', (string) $kasirId, [
            'actor_id' => (string) $kasirId,
            'role' => Role::KASIR,
        ]);

        $this->createOnly('admin_transaction_capability_states', 'actor_id', (string) $adminId, [
            'actor_id' => (string) $adminId,
            'active' => true,
        ]);

        $this->createOnly('admin_cashier_area_access_states', 'actor_id', (string) $adminId, [
            'actor_id' => (string) $adminId,
            'active' => true,
        ]);
    }
}
