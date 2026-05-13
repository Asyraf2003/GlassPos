<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class NoteRevisionSurplusDispositionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_revision_surplus_dispositions_table_exists_with_expected_columns(): void
    {
        self::assertTrue(Schema::hasTable('note_revision_surplus_dispositions'));

        foreach ([
            'id',
            'note_revision_settlement_id',
            'note_root_id',
            'note_revision_id',
            'disposition_type',
            'amount_rupiah',
            'before_pending_rupiah',
            'after_pending_rupiah',
            'status',
            'occurred_at',
            'created_at',
            'updated_at',
            'audit_event_id',
        ] as $column) {
            self::assertTrue(
                Schema::hasColumn('note_revision_surplus_dispositions', $column),
                "Missing note_revision_surplus_dispositions.{$column}"
            );
        }
    }

    public function test_note_revision_surplus_dispositions_indexes_and_foreign_keys_exist(): void
    {
        $this->skipUnlessMysqlOrMariaDb();

        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'PRIMARY',
            ['id']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_audit_event_unique',
            ['audit_event_id']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_settlement_idx',
            ['note_revision_settlement_id']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_root_idx',
            ['note_root_id']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_root_status_idx',
            ['note_root_id', 'status']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_settlement_status_idx',
            ['note_revision_settlement_id', 'status']
        );
        $this->assertIndexColumns(
            'note_revision_surplus_dispositions',
            'note_revision_surplus_dispositions_root_occurred_idx',
            ['note_root_id', 'occurred_at']
        );

        $this->assertForeignKeyExists(
            'fk_note_revision_surplus_dispositions_settlement',
            'note_revision_surplus_dispositions',
            'note_revision_settlement_id',
            'note_revision_settlements',
            'id'
        );
        $this->assertForeignKeyExists(
            'fk_note_revision_surplus_dispositions_revision',
            'note_revision_surplus_dispositions',
            'note_revision_id',
            'note_revisions',
            'id'
        );
        $this->assertForeignKeyExists(
            'fk_note_revision_surplus_dispositions_note_root',
            'note_revision_surplus_dispositions',
            'note_root_id',
            'notes',
            'id'
        );
        $this->assertForeignKeyExists(
            'fk_note_revision_surplus_dispositions_audit_event',
            'note_revision_surplus_dispositions',
            'audit_event_id',
            'audit_events',
            'id'
        );
    }

    private function skipUnlessMysqlOrMariaDb(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('MySQL/MariaDB metadata assertions only.');
        }
    }

    /**
     * @param list<string> $expectedColumns
     */
    private function assertIndexColumns(string $table, string $indexName, array $expectedColumns): void
    {
        $rows = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->filter(fn (object $row): bool => (string) $row->Key_name === $indexName)
            ->sortBy(fn (object $row): int => (int) $row->Seq_in_index)
            ->values();

        self::assertNotEmpty($rows->all(), "Index {$indexName} not found on {$table}.");

        $actualColumns = $rows
            ->map(fn (object $row): string => (string) $row->Column_name)
            ->all();

        self::assertSame($expectedColumns, $actualColumns, "Unexpected columns for {$indexName}.");
    }

    private function assertForeignKeyExists(
        string $constraintName,
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
    ): void {
        $databaseName = (string) DB::connection()->getDatabaseName();

        $row = DB::selectOne(
            'SELECT
                k.CONSTRAINT_NAME,
                k.TABLE_NAME,
                k.COLUMN_NAME,
                k.REFERENCED_TABLE_NAME,
                k.REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE k
             WHERE k.TABLE_SCHEMA = ?
               AND k.TABLE_NAME = ?
               AND k.COLUMN_NAME = ?
               AND k.CONSTRAINT_NAME = ?
               AND k.REFERENCED_TABLE_NAME = ?
               AND k.REFERENCED_COLUMN_NAME = ?
             LIMIT 1',
            [
                $databaseName,
                $table,
                $column,
                $constraintName,
                $referencedTable,
                $referencedColumn,
            ]
        );

        self::assertNotNull($row, "Foreign key {$constraintName} not found.");
    }
}
