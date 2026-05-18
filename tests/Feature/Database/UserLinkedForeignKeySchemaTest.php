<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class UserLinkedForeignKeySchemaTest extends TestCase
{
    public function test_user_linked_app_tables_keep_users_id_compatible_foreign_keys(): void
    {
        self::assertTrue(Schema::hasColumn('users', 'id'), 'Missing users.id');

        $this->assertUserForeignKey('push_subscriptions', 'user_id');
        $this->assertUserForeignKey('mobile_api_tokens', 'user_id');
    }

    private function assertUserForeignKey(string $table, string $column): void
    {
        self::assertTrue(Schema::hasColumn($table, $column), "Missing {$table}.{$column}");

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $userId = $this->mysqlColumn('users', 'id');
            $foreignId = $this->mysqlColumn($table, $column);

            self::assertSame(
                strtolower((string) $userId->COLUMN_TYPE),
                strtolower((string) $foreignId->COLUMN_TYPE),
                "{$table}.{$column} must match users.id column type"
            );

            $fk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->where('REFERENCED_TABLE_NAME', 'users')
                ->where('REFERENCED_COLUMN_NAME', 'id')
                ->first(['CONSTRAINT_NAME']);

            self::assertNotNull($fk, "Missing {$table}.{$column} foreign key to users.id");

            $rule = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
                ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
                ->where('CONSTRAINT_NAME', (string) $fk->CONSTRAINT_NAME)
                ->value('DELETE_RULE');

            self::assertSame('CASCADE', strtoupper((string) $rule), "{$table}.{$column} must cascade on user delete");

            return;
        }

        if ($driver === 'pgsql') {
            $userType = $this->pgsqlColumnType('users', 'id');
            $foreignType = $this->pgsqlColumnType($table, $column);

            self::assertSame($userType, $foreignType, "{$table}.{$column} must match users.id column type");

            $fk = DB::selectOne(
                "
                SELECT rc.delete_rule
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name
                 AND tc.table_schema = kcu.table_schema
                JOIN information_schema.referential_constraints rc
                  ON tc.constraint_name = rc.constraint_name
                 AND tc.constraint_schema = rc.constraint_schema
                JOIN information_schema.constraint_column_usage ccu
                  ON rc.unique_constraint_name = ccu.constraint_name
                 AND rc.unique_constraint_schema = ccu.constraint_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND tc.table_schema = 'public'
                  AND tc.table_name = ?
                  AND kcu.column_name = ?
                  AND ccu.table_name = 'users'
                  AND ccu.column_name = 'id'
                ",
                [$table, $column]
            );

            self::assertNotNull($fk, "Missing {$table}.{$column} foreign key to users.id");
            self::assertSame('CASCADE', strtoupper((string) $fk->delete_rule), "{$table}.{$column} must cascade on user delete");

            return;
        }

        $this->markTestSkipped("User FK schema assertion is not defined for {$driver}");
    }

    private function mysqlColumn(string $table, string $column): object
    {
        $row = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['DATA_TYPE', 'COLUMN_TYPE']);

        self::assertNotNull($row, "Missing metadata for {$table}.{$column}");

        return $row;
    }

    private function pgsqlColumnType(string $table, string $column): string
    {
        $type = DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('data_type');

        return strtolower((string) $type);
    }
}
