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
        Schema::table('products', function (Blueprint $table): void {
            $table->string('nama_barang_normalized')->nullable();
            $table->string('merek_normalized')->nullable();

            $table->index('nama_barang_normalized', 'products_nama_barang_normalized_idx');
            $table->index('merek_normalized', 'products_merek_normalized_idx');
        });

        DB::table('products')
            ->select('id', 'nama_barang', 'merek')
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                DB::table('products')
                    ->where('id', $row->id)
                    ->update([
                        'nama_barang_normalized' => $this->normalize((string) $row->nama_barang),
                        'merek_normalized' => $this->normalize((string) $row->merek),
                    ]);
            });

        Schema::table('products', function (Blueprint $table): void {
            $table->unique('kode_barang', 'products_kode_barang_unique');
            $table->unique(
                ['nama_barang_normalized', 'merek_normalized', 'ukuran'],
                'products_business_identity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_business_identity_unique');
            $table->dropUnique('products_kode_barang_unique');

            $table->dropIndex('products_merek_normalized_idx');
            $table->dropIndex('products_nama_barang_normalized_idx');

            $table->dropColumn([
                'nama_barang_normalized',
                'merek_normalized',
            ]);
        });
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }
};
