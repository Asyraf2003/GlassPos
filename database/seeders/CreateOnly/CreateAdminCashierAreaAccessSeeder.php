<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateAdminCashierAreaAccessSeeder extends CreateOnlySeeder
{
    private const TARGET_TABLE = 'admin_cashier_area_access_states';

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $actorId = $this->resolveActiveAdminActorId();

        $created = $this->createOnly(self::TARGET_TABLE, 'actor_id', $actorId, [
            'actor_id' => $actorId,
            'active' => true,
        ]) ? 1 : 0;

        $this->command?->info(sprintf(
            'create-only admin cashier area access: actor_id=%s created=%d',
            $actorId,
            $created
        ));
    }

    private function resolveActiveAdminActorId(): string
    {
        $row = DB::table('admin_transaction_capability_states as capability')
            ->join('actor_accesses as access', 'access.actor_id', '=', 'capability.actor_id')
            ->where('capability.active', true)
            ->where('access.role', Role::ADMIN)
            ->orderBy('capability.actor_id')
            ->select('capability.actor_id')
            ->first();

        if ($row === null) {
            throw new RuntimeException('No active admin transaction capability actor found.');
        }

        $actorId = trim((string) $row->actor_id);

        if ($actorId === '') {
            throw new RuntimeException('Resolved admin actor id is empty.');
        }

        return $actorId;
    }
}
