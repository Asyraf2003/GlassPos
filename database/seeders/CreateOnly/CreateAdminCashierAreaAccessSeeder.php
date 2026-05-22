<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateAdminCashierAreaAccessSeeder extends Seeder
{
    private const TARGET_TABLE = 'admin_cashier_area_access_states';

    public function run(): void
    {
        $this->guardEnvironment();
        $this->guardSchema();

        $actorId = $this->resolveActiveAdminActorId();

        $created = 0;

        $exists = DB::table(self::TARGET_TABLE)
            ->where('actor_id', $actorId)
            ->exists();

        if (! $exists) {
            DB::table(self::TARGET_TABLE)->insert([
                'actor_id' => $actorId,
                'active' => true,
            ]);

            $created = 1;
        }

        $this->command?->info(sprintf(
            'create-only admin cashier area access: actor_id=%s created=%d',
            $actorId,
            $created
        ));
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('CreateAdminCashierAreaAccessSeeder may only run in local/testing environment.');
        }
    }

    private function guardSchema(): void
    {
        foreach ([self::TARGET_TABLE, 'actor_accesses', 'admin_transaction_capability_states'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('Required table missing: %s.', $table));
            }
        }

        $requiredColumns = [
            self::TARGET_TABLE => ['actor_id', 'active'],
            'actor_accesses' => ['actor_id', 'role'],
            'admin_transaction_capability_states' => ['actor_id', 'active'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(sprintf('Required column missing: %s.%s.', $table, $column));
                }
            }
        }
    }

    private function resolveActiveAdminActorId(): string
    {
        $row = DB::table('admin_transaction_capability_states as capability')
            ->join('actor_accesses as access', 'access.actor_id', '=', 'capability.actor_id')
            ->where('capability.active', true)
            ->where('access.role', 'admin')
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
