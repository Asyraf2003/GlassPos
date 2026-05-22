<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\IdentityAccess\Role\Role;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Predictable seeded users are only allowed in local/testing environments.'
            );
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'pak bos',
                'password' => Hash::make('12345678'),
            ],
        );

        $kasir = User::query()->updateOrCreate(
            ['email' => 'kasir@gmail.com'],
            [
                'name' => 'kasir',
                'password' => Hash::make('12345678'),
            ],
        );

        DB::table('actor_accesses')->upsert([
            [
                'actor_id' => (string) $admin->id,
                'role' => Role::ADMIN,
            ],
            [
                'actor_id' => (string) $kasir->id,
                'role' => Role::KASIR,
            ],
        ], ['actor_id'], ['role']);

        DB::table('admin_cashier_area_access_states')->upsert([
            [
                'actor_id' => (string) $admin->id,
                'active' => true,
            ],
        ], ['actor_id'], ['active']);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $admin->id],
            ['active' => true]
        );
    }
}
