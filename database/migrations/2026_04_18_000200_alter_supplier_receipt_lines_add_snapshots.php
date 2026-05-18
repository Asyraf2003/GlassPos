<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_receipt_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_receipt_lines', 'product_id_snapshot')) {
                $table->string('product_id_snapshot')->nullable();
            }

            if (! Schema::hasColumn('supplier_receipt_lines', 'product_kode_barang_snapshot')) {
                $table->string('product_kode_barang_snapshot')->nullable();
            }

            if (! Schema::hasColumn('supplier_receipt_lines', 'product_nama_barang_snapshot')) {
                $table->string('product_nama_barang_snapshot')->nullable();
            }

            if (! Schema::hasColumn('supplier_receipt_lines', 'product_merek_snapshot')) {
                $table->string('product_merek_snapshot')->nullable();
            }

            if (! Schema::hasColumn('supplier_receipt_lines', 'product_ukuran_snapshot')) {
                $table->integer('product_ukuran_snapshot')->nullable();
            }

            if (! Schema::hasColumn('supplier_receipt_lines', 'unit_cost_rupiah_snapshot')) {
                $table->integer('unit_cost_rupiah_snapshot')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_receipt_lines', function (Blueprint $table): void {
            $columns = [];

            foreach ([
                'product_id_snapshot',
                'product_kode_barang_snapshot',
                'product_nama_barang_snapshot',
                'product_merek_snapshot',
                'product_ukuran_snapshot',
                'unit_cost_rupiah_snapshot',
            ] as $column) {
                if (Schema::hasColumn('supplier_receipt_lines', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
