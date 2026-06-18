<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceProductTemplate;

use App\Adapters\Out\ServiceProductTemplate\DatabaseServiceProductTemplateLookupReaderAdapter;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceProductTemplateLookupReaderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_lookup_reader_port_to_database_adapter(): void
    {
        $reader = $this->app->make(ServiceProductTemplateLookupReaderPort::class);

        self::assertInstanceOf(DatabaseServiceProductTemplateLookupReaderAdapter::class, $reader);
    }

    public function test_it_finds_active_template_by_product_id_with_active_service_catalog_item(): void
    {
        $this->insertProduct('product-template-reader-1', 'SPT-READ-001', 100000);
        $this->insertProduct('product-template-reader-other', 'SPT-READ-OTHER', 90000);

        $this->insertServiceCatalogItem('service-template-reader-inactive', 'Jasa Inactive Template', false);
        $this->insertServiceCatalogItem('service-template-reader-active', 'Jasa Pasang Template Aktif', true);

        $this->insertTemplate(
            id: 'template-active-but-service-inactive',
            productId: 'product-template-reader-1',
            serviceCatalogItemId: 'service-template-reader-inactive',
            defaultServicePriceRupiah: 1000,
            defaultPackageTotalRupiah: 101000,
            isActive: true,
            sortOrder: 1,
        );

        $this->insertTemplate(
            id: 'template-inactive',
            productId: 'product-template-reader-1',
            serviceCatalogItemId: 'service-template-reader-active',
            defaultServicePriceRupiah: 2000,
            defaultPackageTotalRupiah: 102000,
            isActive: false,
            sortOrder: 1,
        );

        $this->insertTemplate(
            id: 'template-active-higher-sort',
            productId: 'product-template-reader-1',
            serviceCatalogItemId: 'service-template-reader-active',
            defaultServicePriceRupiah: 30000,
            defaultPackageTotalRupiah: 130000,
            isActive: true,
            sortOrder: 20,
        );

        $this->insertTemplate(
            id: 'template-active-selected',
            productId: 'product-template-reader-1',
            serviceCatalogItemId: 'service-template-reader-active',
            defaultServicePriceRupiah: 50000,
            defaultPackageTotalRupiah: 150000,
            isActive: true,
            sortOrder: 10,
        );

        $this->insertTemplate(
            id: 'template-other-product',
            productId: 'product-template-reader-other',
            serviceCatalogItemId: 'service-template-reader-active',
            defaultServicePriceRupiah: 9000,
            defaultPackageTotalRupiah: 99000,
            isActive: true,
            sortOrder: 0,
        );

        $row = $this->reader()->findActiveByProductId('product-template-reader-1');

        self::assertNotNull($row);
        self::assertSame('template-active-selected', $row->id);
        self::assertSame('product-template-reader-1', $row->productId);
        self::assertSame('service-template-reader-active', $row->serviceCatalogItemId);
        self::assertSame('Jasa Pasang Template Aktif', $row->serviceName);
        self::assertSame(50000, $row->defaultServicePriceRupiah);
        self::assertSame(150000, $row->defaultPackageTotalRupiah);
        self::assertTrue($row->isActive);
    }

    public function test_it_returns_null_when_product_has_no_active_template(): void
    {
        $this->insertProduct('product-template-reader-empty', 'SPT-READ-EMPTY', 100000);
        $this->insertServiceCatalogItem('service-template-reader-null', 'Jasa Null Template', true);

        $this->insertTemplate(
            id: 'template-reader-inactive-only',
            productId: 'product-template-reader-empty',
            serviceCatalogItemId: 'service-template-reader-null',
            defaultServicePriceRupiah: 50000,
            defaultPackageTotalRupiah: null,
            isActive: false,
            sortOrder: 0,
        );

        self::assertNull($this->reader()->findActiveByProductId('product-template-reader-empty'));
        self::assertNull($this->reader()->findActiveByProductId(''));
        self::assertNull($this->reader()->findActiveByProductId('   '));
    }

    private function reader(): ServiceProductTemplateLookupReaderPort
    {
        return $this->app->make(ServiceProductTemplateLookupReaderPort::class);
    }

    private function insertProduct(string $id, string $kodeBarang, int $hargaJual): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => 'Produk ' . $id,
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => $hargaJual,
            'nama_barang_normalized' => mb_strtolower('Produk ' . $id),
            'merek_normalized' => 'federal',
        ]);
    }

    private function insertServiceCatalogItem(string $id, string $name, bool $isActive): void
    {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'default_price_rupiah' => 50000,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTemplate(
        string $id,
        string $productId,
        string $serviceCatalogItemId,
        int $defaultServicePriceRupiah,
        ?int $defaultPackageTotalRupiah,
        bool $isActive,
        int $sortOrder,
    ): void {
        DB::table('service_product_templates')->insert([
            'id' => $id,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceCatalogItemId,
            'default_service_price_rupiah' => $defaultServicePriceRupiah,
            'default_package_total_rupiah' => $defaultPackageTotalRupiah,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'created_at' => now()->subMinutes($sortOrder + 1),
            'updated_at' => now()->subMinutes($sortOrder + 1),
        ]);
    }
}
