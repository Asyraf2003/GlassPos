<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SupplierBankMigrationFeatureTest extends TestCase
{
    public function test_additive_migration_keeps_existing_rows_null(): void
    {
        // Isolate DDL from the transactional MySQL feature suite.
        config(['database.default' => 'supplier_migration', 'database.connections.supplier_migration' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('nama_pt_pengirim');
        });
        DB::table('suppliers')->insert(['id' => 'legacy', 'nama_pt_pengirim' => 'Legacy']);
        $migration = require database_path('migrations/2026_10_01_000001_add_bank_details_to_suppliers.php');
        $migration->up();
        $row = DB::table('suppliers')->where('id', 'legacy')->first();
        self::assertNull($row->bank_name);
        self::assertNull($row->bank_account_number);
        self::assertSame('Legacy', $row->nama_pt_pengirim);
        $migration->down();
        self::assertFalse(Schema::hasColumn('suppliers', 'bank_name'));
        self::assertFalse(Schema::hasColumn('suppliers', 'bank_account_number'));
        self::assertSame('Legacy', DB::table('suppliers')->value('nama_pt_pengirim'));
    }
}
