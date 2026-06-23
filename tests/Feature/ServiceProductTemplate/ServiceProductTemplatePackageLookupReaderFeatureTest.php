<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceProductTemplate;

use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceProductTemplatePackageLookupReaderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_active_packages_with_service_and_three_product_lines(): void
    {
        $this->insertProduct('package-product-kopling', 'PKG-KOPLING', 'Kampas Kopling', 120000, 5);
        $this->insertProduct('package-product-oli', 'PKG-OLI', 'Oli Transmisi', 45000, 4);
        $this->insertProduct('package-product-seal', 'PKG-SEAL', 'Seal Persneling', 25000, 2);
        $this->insertProduct('package-product-other', 'PKG-OTHER', 'Busi Racing', 30000, 10);

        $this->insertServiceCatalogItem('package-service-kopling', 'Ganti Kampas Kopling', true);
        $this->insertServiceCatalogItem('package-service-inactive', 'Jasa Inactive Package', false);

        $this->insertTemplate(
            id: 'package-template-kopling',
            productId: 'package-product-kopling',
            serviceCatalogItemId: 'package-service-kopling',
            isActive: true,
            sortOrder: 0,
        );
        $this->insertTemplate(
            id: 'package-template-inactive-service',
            productId: 'package-product-other',
            serviceCatalogItemId: 'package-service-inactive',
            isActive: true,
            sortOrder: 1,
        );
        $this->insertTemplate(
            id: 'package-template-inactive-template',
            productId: 'package-product-other',
            serviceCatalogItemId: 'package-service-kopling',
            isActive: false,
            sortOrder: 2,
        );

        $this->insertLine('package-line-0', 'package-template-kopling', 'package-product-kopling', 1, 0);
        $this->insertLine('package-line-1', 'package-template-kopling', 'package-product-oli', 2, 1);
        $this->insertLine('package-line-2', 'package-template-kopling', 'package-product-seal', 1, 2);

        $rows = $this->reader()->searchActivePackages('kopling');

        self::assertCount(1, $rows);

        $row = $rows[0];

        self::assertSame('package-template-kopling', $row->id);
        self::assertSame('package-product-kopling', $row->legacyProductId);
        self::assertSame('package-service-kopling', $row->serviceCatalogItemId);
        self::assertSame('Ganti Kampas Kopling', $row->serviceName);
        self::assertSame(150000, $row->defaultServicePriceRupiah);
        self::assertSame(240000, $row->defaultPackageTotalRupiah);
        self::assertTrue($row->isActive);
        self::assertTrue($row->hasSufficientStock());
        self::assertSame(
            'Kampas Kopling — Federal — 80 (PKG-KOPLING) + Oli Transmisi — Federal — 80 (PKG-OLI) + Seal Persneling — Federal — 80 (PKG-SEAL)',
            $row->productSummaryLabel(),
        );

        self::assertCount(3, $row->productLines);
        self::assertSame('package-product-kopling', $row->productLines[0]->productId);
        self::assertSame(1, $row->productLines[0]->qty);
        self::assertSame(0, $row->productLines[0]->sortOrder);
        self::assertSame(5, $row->productLines[0]->availableStock);
        self::assertSame(120000, $row->productLines[0]->defaultUnitPriceRupiah);

        self::assertSame('package-product-oli', $row->productLines[1]->productId);
        self::assertSame(2, $row->productLines[1]->qty);
        self::assertSame(1, $row->productLines[1]->sortOrder);
        self::assertSame(4, $row->productLines[1]->availableStock);
        self::assertSame(45000, $row->productLines[1]->minimumUnitPriceRupiah);
    }

    public function test_it_searches_package_by_product_line_keyword_and_falls_back_to_legacy_product(): void
    {
        $this->insertProduct('legacy-package-product', 'LEGACY-PKG', 'Filter Udara Legacy', 55000, 3);
        $this->insertServiceCatalogItem('legacy-package-service', 'Servis Filter Udara', true);
        $this->insertTemplate(
            id: 'legacy-package-template',
            productId: 'legacy-package-product',
            serviceCatalogItemId: 'legacy-package-service',
            isActive: true,
            sortOrder: 0,
        );

        $rows = $this->reader()->searchActivePackages('filter udara');

        self::assertCount(1, $rows);
        self::assertSame('legacy-package-template', $rows[0]->id);
        self::assertCount(1, $rows[0]->productLines);
        self::assertSame('legacy-package-product', $rows[0]->productLines[0]->productId);
        self::assertSame(1, $rows[0]->productLines[0]->qty);
        self::assertSame(0, $rows[0]->productLines[0]->sortOrder);
    }

    public function test_it_returns_empty_rows_for_short_or_unmatched_query(): void
    {
        $this->insertProduct('package-product-empty', 'PKG-EMPTY', 'Kabel Gas', 25000, 7);
        $this->insertServiceCatalogItem('package-service-empty', 'Jasa Kabel Gas', true);
        $this->insertTemplate(
            id: 'package-template-empty',
            productId: 'package-product-empty',
            serviceCatalogItemId: 'package-service-empty',
            isActive: true,
            sortOrder: 0,
        );

        self::assertSame([], $this->reader()->searchActivePackages('k'));
        self::assertSame([], $this->reader()->searchActivePackages('tidak-ada-paket-begini'));
    }

    private function reader(): ServiceProductTemplateLookupReaderPort
    {
        return $this->app->make(ServiceProductTemplateLookupReaderPort::class);
    }

    private function insertProduct(
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

    private function insertServiceCatalogItem(string $id, string $name, bool $isActive): void
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

    private function insertTemplate(
        string $id,
        string $productId,
        string $serviceCatalogItemId,
        bool $isActive,
        int $sortOrder,
    ): void {
        DB::table('service_product_templates')->insert([
            'id' => $id,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceCatalogItemId,
            'default_service_price_rupiah' => 150000,
            'default_package_total_rupiah' => 240000,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLine(
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
