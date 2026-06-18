<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierProductLookupServiceProductTemplateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_lookup_without_service_product_context_keeps_legacy_product_only_shape(): void
    {
        $this->loginAsKasir();
        $this->seedProductWithInventory('product-template-lookup-1', 'SPT-LOOKUP-001', 'Ban Template Lookup', 125000, 5);
        $this->seedServiceCatalogItem('service-template-lookup-1', 'Jasa Pasang Ban Template', true);
        $this->seedTemplate('template-lookup-1', 'product-template-lookup-1', 'service-template-lookup-1');

        $response = $this->getJson(route('cashier.notes.products.lookup', ['q' => 'Ban']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.id', 'product-template-lookup-1');
        $response->assertJsonPath('data.rows.0.default_unit_price_rupiah', 125000);

        $row = $response->json('data.rows.0');

        self::assertIsArray($row);
        self::assertArrayNotHasKey('service_product_template', $row);
    }

    public function test_product_lookup_with_service_product_context_includes_active_template_metadata(): void
    {
        $this->loginAsKasir();
        $this->seedProductWithInventory('product-template-lookup-1', 'SPT-LOOKUP-001', 'Ban Template Lookup', 125000, 5);
        $this->seedServiceCatalogItem('service-template-lookup-1', 'Jasa Pasang Ban Template', true);
        $this->seedTemplate('template-lookup-1', 'product-template-lookup-1', 'service-template-lookup-1');

        $response = $this->getJson(route('cashier.notes.products.lookup', [
            'q' => 'Ban',
            'context' => 'service_product',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.id', 'product-template-lookup-1');
        $response->assertJsonPath('data.rows.0.default_unit_price_rupiah', 125000);
        $response->assertJsonPath('data.rows.0.service_product_template.id', 'template-lookup-1');
        $response->assertJsonPath('data.rows.0.service_product_template.service_catalog_item_id', 'service-template-lookup-1');
        $response->assertJsonPath('data.rows.0.service_product_template.service_name', 'Jasa Pasang Ban Template');
        $response->assertJsonPath('data.rows.0.service_product_template.default_service_price_rupiah', 75000);
        $response->assertJsonPath('data.rows.0.service_product_template.default_package_total_rupiah', 200000);
    }

    public function test_product_lookup_with_service_product_context_returns_null_template_when_no_active_template_exists(): void
    {
        $this->loginAsKasir();
        $this->seedProductWithInventory('product-template-lookup-null', 'SPT-LOOKUP-NULL', 'Ban Template Null', 125000, 5);

        $response = $this->getJson(route('cashier.notes.products.lookup', [
            'q' => 'Ban',
            'context' => 'service_product',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.id', 'product-template-lookup-null');
        $response->assertJsonPath('data.rows.0.service_product_template', null);
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
            'default_price_rupiah' => 75000,
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
            'default_service_price_rupiah' => 75000,
            'default_package_total_rupiah' => 200000,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
