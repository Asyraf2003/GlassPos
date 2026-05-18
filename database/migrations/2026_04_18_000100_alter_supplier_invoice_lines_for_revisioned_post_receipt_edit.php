<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_invoice_lines', 'revision_no')) {
                $table->integer('revision_no')->default(1);
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'is_current')) {
                $table->boolean('is_current')->default(true);
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'source_line_id')) {
                $table->string('source_line_id')->nullable();
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'superseded_by_line_id')) {
                $table->string('superseded_by_line_id')->nullable();
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'superseded_at')) {
                $table->timestamp('superseded_at')->nullable();
            }
        });

        $this->dropUniqueIfExists('supplier_invoice_lines', 'sil_supplier_invoice_line_no_unique');

        $this->createIndexIfMissing(
            'supplier_invoice_lines',
            ['supplier_invoice_id', 'is_current'],
            'sil_supplier_invoice_is_current_idx'
        );

        $this->createIndexIfMissing(
            'supplier_invoice_lines',
            ['source_line_id'],
            'sil_source_line_idx'
        );

        $this->createIndexIfMissing(
            'supplier_invoice_lines',
            ['superseded_by_line_id'],
            'sil_superseded_by_line_idx'
        );

        $this->createUniqueIfMissing(
            'supplier_invoice_lines',
            ['supplier_invoice_id', 'revision_no', 'line_no'],
            'sil_supplier_invoice_revision_line_no_unique'
        );
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('supplier_invoice_lines', 'sil_supplier_invoice_revision_line_no_unique');
        $this->dropIndexIfExists('supplier_invoice_lines', 'sil_supplier_invoice_is_current_idx');
        $this->dropIndexIfExists('supplier_invoice_lines', 'sil_source_line_idx');
        $this->dropIndexIfExists('supplier_invoice_lines', 'sil_superseded_by_line_idx');

        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            $columns = [];

            foreach ([
                'revision_no',
                'is_current',
                'source_line_id',
                'superseded_by_line_id',
                'superseded_at',
            ] as $column) {
                if (Schema::hasColumn('supplier_invoice_lines', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->createUniqueIfMissing(
            'supplier_invoice_lines',
            ['supplier_invoice_id', 'line_no'],
            'sil_supplier_invoice_line_no_unique'
        );
    }

    private function createUniqueIfMissing(string $table, array $columns, string $index): void
    {
        if ($this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->unique($columns, $index);
        });
    }

    private function createIndexIfMissing(string $table, array $columns, string $index): void
    {
        if ($this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropUniqueIfExists(string $table, string $index): void
    {
        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropUnique($index);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            if (($row->Key_name ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
