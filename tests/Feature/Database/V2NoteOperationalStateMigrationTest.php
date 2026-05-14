<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class V2NoteOperationalStateMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_table_has_operational_state_columns(): void
    {
        foreach ([
            'note_state',
            'closed_at',
            'closed_by_actor_id',
            'reopened_at',
            'reopened_by_actor_id',
            'created_at',
            'updated_at',
        ] as $column) {
            self::assertTrue(Schema::hasColumn('notes', $column), "Missing notes.{$column}");
        }
    }

    public function test_notes_operational_state_columns_have_expected_defaults_on_insert(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi',
            'customer_phone' => null,
            'transaction_date' => '2026-04-03',
            'total_rupiah' => 10000,
        ]);

        $row = DB::table('notes')->where('id', 'note-1')->first();

        self::assertNotNull($row);
        self::assertSame('open', (string) $row->note_state);
        self::assertNull($row->closed_at);
        self::assertNull($row->closed_by_actor_id);
        self::assertNull($row->reopened_at);
        self::assertNull($row->reopened_by_actor_id);
    }

    public function test_notes_table_has_expected_operational_state_indexes(): void
    {
        $this->skipUnlessMysqlOrMariaDb();

        $this->assertIndexColumns('notes', 'notes_transaction_date_index', ['transaction_date']);
        $this->assertIndexColumns('notes', 'notes_customer_name_index', ['customer_name']);
        $this->assertIndexColumns('notes', 'notes_note_state_index', ['note_state']);
        $this->assertIndexColumns('notes', 'notes_closed_at_index', ['closed_at']);
    }

    private function skipUnlessMysqlOrMariaDb(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('MySQL/MariaDB metadata assertions only.');
        }
    }

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

        self::assertSame($expectedColumns, $actualColumns, "Unexpected columns for {$indexName} on {$table}.");
    }
}
