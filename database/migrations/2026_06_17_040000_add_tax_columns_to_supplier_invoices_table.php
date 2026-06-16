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
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->integer('subtotal_before_tax_rupiah')->default(0);
            $table->string('tax_input')->nullable();
            $table->string('tax_mode')->default('none');
            $table->integer('tax_rate_basis_points')->nullable();
            $table->integer('tax_amount_rupiah')->default(0);

            $table->index('tax_mode', 'si_tax_mode_idx');
        });

        DB::table('supplier_invoices')
            ->where('subtotal_before_tax_rupiah', 0)
            ->update([
                'subtotal_before_tax_rupiah' => DB::raw('grand_total_rupiah'),
                'tax_mode' => 'none',
                'tax_rate_basis_points' => null,
                'tax_amount_rupiah' => 0,
            ]);
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->dropIndex('si_tax_mode_idx');

            $table->dropColumn([
                'subtotal_before_tax_rupiah',
                'tax_input',
                'tax_mode',
                'tax_rate_basis_points',
                'tax_amount_rupiah',
            ]);
        });
    }
};
