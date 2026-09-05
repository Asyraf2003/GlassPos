<?php

declare(strict_types=1);

namespace App\Adapters\Out\IdentityAccess;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser;
use App\Ports\Out\IdentityAccess\UserAccessWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseUserAccessWriterAdapter implements UserAccessWriterPort
{
    public function findUserIdByEmail(string $email): ?string
    {
        $id = EloquentUser::query()
            ->where('email', mb_strtolower(trim($email)))
            ->value('id');

        return $id === null ? null : (string) $id;
    }

    public function createUser(string $name, string $email, string $password): string
    {
        $user = EloquentUser::query()->create([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return (string) $user->getAuthIdentifier();
    }

    public function createActorAccess(string $actorId, string $role): void
    {
        DB::table('actor_accesses')->insert([
            'actor_id' => trim($actorId),
            'role' => trim($role),
        ]);
    }

    public function enableAdminTransactionCapability(string $actorId): void
    {
        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => trim($actorId),
            'active' => true,
        ]);
    }

    public function enableAdminCashierAreaAccess(string $actorId): void
    {
        DB::table('admin_cashier_area_access_states')->insert([
            'actor_id' => trim($actorId),
            'active' => true,
        ]);
    }
}
