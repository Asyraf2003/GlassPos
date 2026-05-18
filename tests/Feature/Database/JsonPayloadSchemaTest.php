<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class JsonPayloadSchemaTest extends TestCase
{
    public function test_note_and_workspace_payload_columns_use_json_type_or_validated_json_alias(): void
    {
        $this->assertJsonColumn('note_mutation_snapshots', 'payload_json');
        $this->assertJsonColumn('transaction_workspace_drafts', 'payload_json');
    }

    private function assertJsonColumn(string $table, string $column): void
    {
        self::assertTrue(Schema::hasColumn($table, $column), "Missing {$table}.{$column}");

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $type = strtolower((string) DB::table('information_schema.COLUMNS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->value('DATA_TYPE'));

            if ($type === 'json') {
                return;
            }

            self::assertSame('longtext', $type, "{$table}.{$column} should use JSON or validated JSON alias storage");

            $ddl = $this->mysqlCreateTableSql($table);
            $normalizedDdl = strtolower(str_replace([' ', "\n", "\r", "\t"], '', $ddl));

            self::assertStringContainsString(
                sprintf('json_valid(`%s`)', $column),
                $normalizedDdl,
                "{$table}.{$column} longtext JSON alias must have JSON_VALID check constraint"
            );

            return;
        }

        if ($driver === 'pgsql') {
            $type = strtolower((string) DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('column_name', $column)
                ->value('data_type'));

            self::assertContains($type, ['json', 'jsonb'], "{$table}.{$column} should use JSON column type");

            return;
        }

        $this->markTestSkipped("JSON column type assertion is not defined for {$driver}");
    }

    private function mysqlCreateTableSql(string $table): string
    {
        $row = DB::selectOne(sprintf('SHOW CREATE TABLE `%s`', $table));
        $values = array_values((array) $row);

        return (string) ($values[1] ?? '');
    }
}
