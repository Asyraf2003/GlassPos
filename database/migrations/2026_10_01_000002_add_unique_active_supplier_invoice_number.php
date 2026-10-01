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
        $duplicates = DB::table('supplier_invoices')->whereNull('voided_at')
            ->whereNotNull('nomor_faktur_normalized')
            ->groupBy('nomor_faktur_normalized')->havingRaw('COUNT(*) > 1')->exists();

        if ($duplicates) {
            throw new RuntimeException('Resolve duplicate active supplier invoice numbers before installing the unique constraint. No invoice history was changed.');
        }

        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->string('active_nomor_faktur_normalized')->nullable()
                ->virtualAs('CASE WHEN voided_at IS NULL THEN nomor_faktur_normalized ELSE NULL END');
            $table->unique('active_nomor_faktur_normalized', 'si_active_invoice_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->dropUnique('si_active_invoice_number_unique');
            $table->dropColumn('active_nomor_faktur_normalized');
        });
    }
};
