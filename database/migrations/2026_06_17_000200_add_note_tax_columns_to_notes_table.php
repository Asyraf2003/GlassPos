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
        Schema::table('notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('notes', 'subtotal_before_note_tax_rupiah')) {
                $table->integer('subtotal_before_note_tax_rupiah')->default(0)->after('total_rupiah');
            }

            if (! Schema::hasColumn('notes', 'note_tax_input')) {
                $table->string('note_tax_input', 32)->nullable()->after('subtotal_before_note_tax_rupiah');
            }

            if (! Schema::hasColumn('notes', 'note_tax_mode')) {
                $table->string('note_tax_mode', 16)->default('none')->after('note_tax_input');
            }

            if (! Schema::hasColumn('notes', 'note_tax_rate_basis_points')) {
                $table->integer('note_tax_rate_basis_points')->nullable()->after('note_tax_mode');
            }

            if (! Schema::hasColumn('notes', 'note_tax_amount_rupiah')) {
                $table->integer('note_tax_amount_rupiah')->default(0)->after('note_tax_rate_basis_points');
            }
        });

        DB::table('notes')
            ->where('subtotal_before_note_tax_rupiah', 0)
            ->update([
                'subtotal_before_note_tax_rupiah' => DB::raw('total_rupiah'),
                'note_tax_mode' => 'none',
                'note_tax_amount_rupiah' => 0,
            ]);
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            foreach ([
                'note_tax_amount_rupiah',
                'note_tax_rate_basis_points',
                'note_tax_mode',
                'note_tax_input',
                'subtotal_before_note_tax_rupiah',
            ] as $column) {
                if (Schema::hasColumn('notes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
