<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SupplierPaymentProofAttachmentSchemaTest extends TestCase
{
    public function test_file_size_bytes_uses_signed_big_integer_metadata_column(): void
    {
        self::assertTrue(
            Schema::hasColumn('supplier_payment_proof_attachments', 'file_size_bytes'),
            'Missing supplier_payment_proof_attachments.file_size_bytes'
        );

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $column = DB::table('information_schema.COLUMNS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'supplier_payment_proof_attachments')
                ->where('COLUMN_NAME', 'file_size_bytes')
                ->first(['DATA_TYPE', 'COLUMN_TYPE']);

            self::assertSame('bigint', strtolower((string) ($column->DATA_TYPE ?? '')));
            self::assertStringNotContainsString(
                'unsigned',
                strtolower((string) ($column->COLUMN_TYPE ?? '')),
                'file_size_bytes is metadata and should not depend on MySQL unsigned semantics'
            );

            return;
        }

        if ($driver === 'pgsql') {
            $type = DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', 'supplier_payment_proof_attachments')
                ->where('column_name', 'file_size_bytes')
                ->value('data_type');

            self::assertContains(strtolower((string) $type), ['bigint', 'integer']);

            return;
        }

        $this->markTestSkipped("file_size_bytes schema assertion is not defined for {$driver}");
    }
}
