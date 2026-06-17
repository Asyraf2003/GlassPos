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
            if (! Schema::hasColumn('supplier_invoice_lines', 'line_subtotal_before_tax_rupiah')) {
                $table->integer('line_subtotal_before_tax_rupiah')->default(0);
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'tax_input')) {
                $table->string('tax_input')->nullable();
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'tax_mode')) {
                $table->string('tax_mode')->default('none');
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'tax_rate_basis_points')) {
                $table->integer('tax_rate_basis_points')->nullable();
            }

            if (! Schema::hasColumn('supplier_invoice_lines', 'tax_amount_rupiah')) {
                $table->integer('tax_amount_rupiah')->default(0);
            }
        });

        DB::table('supplier_invoice_lines')
            ->where('line_subtotal_before_tax_rupiah', 0)
            ->update([
                'line_subtotal_before_tax_rupiah' => DB::raw('line_total_rupiah'),
                'tax_mode' => 'none',
                'tax_rate_basis_points' => null,
                'tax_amount_rupiah' => 0,
            ]);
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            foreach ([
                'line_subtotal_before_tax_rupiah',
                'tax_input',
                'tax_mode',
                'tax_rate_basis_points',
                'tax_amount_rupiah',
            ] as $column) {
                if (Schema::hasColumn('supplier_invoice_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
