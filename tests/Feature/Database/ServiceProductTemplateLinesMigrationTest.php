<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ServiceProductTemplateLinesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_product_template_lines_table_supports_one_service_with_up_to_three_products(): void
    {
        $this->assertTrue(Schema::hasTable('service_product_template_lines'));

        foreach ([
            'id',
            'service_product_template_id',
            'product_id',
            'qty',
            'sort_order',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('service_product_template_lines', $column),
                'Missing column: ' . $column,
            );
        }

        DB::table('service_catalog_items')->insert([
            'id' => 'service-template-lines-service-1',
            'name' => 'Paket Kopling Lines',
            'normalized_name' => 'paket kopling lines',
            'default_price_rupiah' => 150000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([1, 2, 3] as $index) {
            DB::table('products')->insert([
                'id' => 'service-template-lines-product-' . $index,
                'kode_barang' => 'TPL-LINE-' . $index,
                'nama_barang' => 'Produk Paket Lines ' . $index,
                'merek' => 'Federal',
                'harga_jual' => 10000 * $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('service_product_templates')->insert([
            'id' => 'service-template-lines-template-1',
            'product_id' => 'service-template-lines-product-1',
            'service_catalog_item_id' => 'service-template-lines-service-1',
            'default_service_price_rupiah' => 150000,
            'default_package_total_rupiah' => 210000,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([0, 1, 2] as $sortOrder) {
            DB::table('service_product_template_lines')->insert([
                'id' => 'service-template-lines-line-' . $sortOrder,
                'service_product_template_id' => 'service-template-lines-template-1',
                'product_id' => 'service-template-lines-product-' . ($sortOrder + 1),
                'qty' => 1,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(
            3,
            DB::table('service_product_template_lines')
                ->where('service_product_template_id', 'service-template-lines-template-1')
                ->count(),
        );

        $this->assertDatabaseHas('service_product_template_lines', [
            'service_product_template_id' => 'service-template-lines-template-1',
            'product_id' => 'service-template-lines-product-1',
            'qty' => 1,
            'sort_order' => 0,
        ]);
    }
}
