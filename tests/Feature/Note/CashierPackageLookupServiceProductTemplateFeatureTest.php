<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierPackageLookupServiceProductTemplateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_lookup_package_with_service_and_product_lines_payload(): void
    {
        $this->loginAsKasir();

        $this->seedProductWithInventory('package-endpoint-product-kopling', 'PKG-END-KPL', 'Kampas Kopling', 120000, 5);
        $this->seedProductWithInventory('package-endpoint-product-oli', 'PKG-END-OLI', 'Oli Transmisi', 45000, 4);
        $this->seedServiceCatalogItem('package-endpoint-service-kopling', 'Ganti Kampas Kopling', true);
        $this->seedTemplate('package-endpoint-template-kopling', 'package-endpoint-product-kopling', 'package-endpoint-service-kopling');

        $this->seedLine('package-endpoint-line-0', 'package-endpoint-template-kopling', 'package-endpoint-product-kopling', 1, 0);
        $this->seedLine('package-endpoint-line-1', 'package-endpoint-template-kopling', 'package-endpoint-product-oli', 2, 1);

        $response = $this->getJson(route('cashier.notes.packages.lookup', [
            'q' => 'kopling',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.id', 'package-endpoint-template-kopling');
        $response->assertJsonPath(
            'data.rows.0.label',
            'Paket Ganti Kampas Kopling · Service: Ganti Kampas Kopling · Produk: Kampas Kopling + Oli Transmisi · Harga Servis 150.000 · stok aman',
        );
        $response->assertJsonPath(
            'data.rows.0.description',
            'Service: Ganti Kampas Kopling · Produk: Kampas Kopling + Oli Transmisi · Harga Servis 150.000 · stok aman',
        );
        $response->assertJsonPath('data.rows.0.stock_status', 'safe');
        $response->assertJsonPath('data.rows.0.service.name', 'Ganti Kampas Kopling');
        $response->assertJsonPath('data.rows.0.service.price_rupiah', 150000);
        $response->assertJsonPath('data.rows.0.service_product_template.id', 'package-endpoint-template-kopling');
        $response->assertJsonPath('data.rows.0.service_product_template.default_package_total_rupiah', 240000);

        $response->assertJsonPath('data.rows.0.product_lines.0.product_id', 'package-endpoint-product-kopling');
        $response->assertJsonPath('data.rows.0.product_lines.0.qty', 1);
        $response->assertJsonPath('data.rows.0.product_lines.0.unit_price_rupiah', 120000);
        $response->assertJsonPath('data.rows.0.product_lines.0.available_stock', 5);
        $response->assertJsonPath('data.rows.0.product_lines.0.stock_status', 'safe');

        $response->assertJsonPath('data.rows.0.product_lines.1.product_id', 'package-endpoint-product-oli');
        $response->assertJsonPath('data.rows.0.product_lines.1.qty', 2);
        $response->assertJsonPath('data.rows.0.product_lines.1.unit_price_rupiah', 45000);
        $response->assertJsonPath('data.rows.0.product_lines.1.available_stock', 4);
        $response->assertJsonPath('data.rows.0.product_lines.1.stock_status', 'safe');
    }

    public function test_cashier_package_lookup_marks_insufficient_stock(): void
    {
        $this->loginAsKasir();

        $this->seedProductWithInventory('package-endpoint-product-low', 'PKG-END-LOW', 'Kampas Ganda', 90000, 1);
        $this->seedServiceCatalogItem('package-endpoint-service-low', 'Ganti Kampas Ganda', true);
        $this->seedTemplate('package-endpoint-template-low', 'package-endpoint-product-low', 'package-endpoint-service-low');
        $this->seedLine('package-endpoint-line-low', 'package-endpoint-template-low', 'package-endpoint-product-low', 2, 0);

        $response = $this->getJson(route('cashier.notes.packages.lookup', [
            'q' => 'kampas ganda',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.stock_status', 'insufficient');
        $response->assertJsonPath('data.rows.0.stock_label', 'stok kurang');
        $response->assertJsonPath('data.rows.0.product_lines.0.stock_status', 'insufficient');
    }

    public function test_cashier_package_lookup_returns_empty_rows_for_short_query(): void
    {
        $this->loginAsKasir();

        $this->getJson(route('cashier.notes.packages.lookup', ['q' => 'k']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.rows');
    }

    private function seedProductWithInventory(
        string $id,
        string $kodeBarang,
        string $name,
        int $hargaJual,
        int $qtyOnHand,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $name,
            'nama_barang_normalized' => mb_strtolower($name),
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 80,
            'harga_jual' => $hargaJual,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => $qtyOnHand,
        ]);
    }

    private function seedServiceCatalogItem(string $id, string $name, bool $isActive): void
    {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'default_price_rupiah' => 150000,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTemplate(string $id, string $productId, string $serviceCatalogItemId): void
    {
        DB::table('service_product_templates')->insert([
            'id' => $id,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceCatalogItemId,
            'default_service_price_rupiah' => 150000,
            'default_package_total_rupiah' => 240000,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedLine(
        string $id,
        string $templateId,
        string $productId,
        int $qty,
        int $sortOrder,
    ): void {
        DB::table('service_product_template_lines')->insert([
            'id' => $id,
            'service_product_template_id' => $templateId,
            'product_id' => $productId,
            'qty' => $qty,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
