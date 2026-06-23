<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplateLookupReaderAdapter implements ServiceProductTemplateLookupReaderPort
{
    public function findActiveByProductId(string $productId): ?ServiceProductTemplateLookupRow
    {
        $trimmedProductId = trim($productId);

        if ($trimmedProductId === '') {
            return null;
        }

        $row = DB::table('service_product_templates')
            ->join(
                'service_catalog_items',
                'service_catalog_items.id',
                '=',
                'service_product_templates.service_catalog_item_id'
            )
            ->where('service_product_templates.product_id', $trimmedProductId)
            ->where('service_product_templates.is_active', true)
            ->where('service_catalog_items.is_active', true)
            ->select([
                'service_product_templates.id',
                'service_product_templates.product_id',
                'service_product_templates.service_catalog_item_id',
                'service_product_templates.default_service_price_rupiah',
                'service_product_templates.default_package_total_rupiah',
                'service_product_templates.is_active',
                'service_catalog_items.name as service_name',
            ])
            ->orderBy('service_product_templates.sort_order')
            ->orderByDesc('service_product_templates.updated_at')
            ->orderBy('service_product_templates.id')
            ->first();

        return $row === null ? null : $this->map($row);
    }

    /**
     * @return list<ServiceProductTemplatePackageLookupRow>
     */
    public function searchActivePackages(
        string $query,
        int $limit = ServiceProductTemplateLookupReaderPort::DEFAULT_PACKAGE_LIMIT,
    ): array {
        $trimmedQuery = trim($query);

        if (mb_strlen($trimmedQuery) < 2) {
            return [];
        }

        $builder = DB::table('service_product_templates')
            ->join(
                'service_catalog_items',
                'service_catalog_items.id',
                '=',
                'service_product_templates.service_catalog_item_id'
            )
            ->where('service_product_templates.is_active', true)
            ->where('service_catalog_items.is_active', true)
            ->select([
                'service_product_templates.id',
                'service_product_templates.product_id as legacy_product_id',
                'service_product_templates.service_catalog_item_id',
                'service_product_templates.default_service_price_rupiah',
                'service_product_templates.default_package_total_rupiah',
                'service_product_templates.is_active',
                'service_catalog_items.name as service_name',
            ]);

        $this->applyPackageSearch($builder, $trimmedQuery);

        $rows = $builder
            ->orderBy('service_product_templates.sort_order')
            ->orderBy('service_catalog_items.name')
            ->orderBy('service_product_templates.id')
            ->limit($this->boundedPackageLimit($limit))
            ->get();

        $packages = [];

        foreach ($rows as $row) {
            $package = $this->mapPackage($row);

            if ($package !== null) {
                $packages[] = $package;
            }
        }

        return $packages;
    }

    private function map(object $row): ServiceProductTemplateLookupRow
    {
        return new ServiceProductTemplateLookupRow(
            id: (string) $row->id,
            productId: (string) $row->product_id,
            serviceCatalogItemId: (string) $row->service_catalog_item_id,
            serviceName: (string) $row->service_name,
            defaultServicePriceRupiah: (int) $row->default_service_price_rupiah,
            defaultPackageTotalRupiah: $row->default_package_total_rupiah !== null
                ? (int) $row->default_package_total_rupiah
                : null,
            isActive: (bool) $row->is_active,
        );
    }

    private function mapPackage(object $row): ?ServiceProductTemplatePackageLookupRow
    {
        $productLines = $this->productLinesForTemplate(
            templateId: (string) $row->id,
            legacyProductId: (string) $row->legacy_product_id,
        );

        if ($productLines === []) {
            return null;
        }

        return new ServiceProductTemplatePackageLookupRow(
            id: (string) $row->id,
            legacyProductId: (string) $row->legacy_product_id,
            serviceCatalogItemId: (string) $row->service_catalog_item_id,
            serviceName: (string) $row->service_name,
            defaultServicePriceRupiah: (int) $row->default_service_price_rupiah,
            defaultPackageTotalRupiah: $row->default_package_total_rupiah !== null
                ? (int) $row->default_package_total_rupiah
                : null,
            isActive: (bool) $row->is_active,
            productLines: $productLines,
        );
    }

    /**
     * @return list<ServiceProductTemplatePackageProductLineRow>
     */
    private function productLinesForTemplate(string $templateId, string $legacyProductId): array
    {
        $rows = DB::table('service_product_template_lines')
            ->join('products', 'products.id', '=', 'service_product_template_lines.product_id')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->where('service_product_template_lines.service_product_template_id', $templateId)
            ->whereNull('products.deleted_at')
            ->select([
                'products.id as product_id',
                'products.kode_barang',
                'products.nama_barang',
                'products.merek',
                'products.ukuran',
                'products.harga_jual',
                'service_product_template_lines.qty',
                'service_product_template_lines.sort_order',
                DB::raw('COALESCE(product_inventory.qty_on_hand, 0) as available_stock'),
            ])
            ->orderBy('service_product_template_lines.sort_order')
            ->orderBy('products.nama_barang')
            ->limit(3)
            ->get();

        if ($rows->isEmpty()) {
            $rows = $this->legacyProductLineRows($legacyProductId);
        }

        return array_map(
            fn (object $line): ServiceProductTemplatePackageProductLineRow => $this->mapProductLine($line),
            $rows->all(),
        );
    }

    private function legacyProductLineRows(string $legacyProductId): \Illuminate\Support\Collection
    {
        return DB::table('products')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->where('products.id', $legacyProductId)
            ->whereNull('products.deleted_at')
            ->select([
                'products.id as product_id',
                'products.kode_barang',
                'products.nama_barang',
                'products.merek',
                'products.ukuran',
                'products.harga_jual',
                DB::raw('1 as qty'),
                DB::raw('0 as sort_order'),
                DB::raw('COALESCE(product_inventory.qty_on_hand, 0) as available_stock'),
            ])
            ->get();
    }

    private function mapProductLine(object $line): ServiceProductTemplatePackageProductLineRow
    {
        return new ServiceProductTemplatePackageProductLineRow(
            productId: (string) $line->product_id,
            kodeBarang: $line->kode_barang !== null ? (string) $line->kode_barang : null,
            productName: (string) $line->nama_barang,
            brand: (string) $line->merek,
            size: $line->ukuran !== null ? (int) $line->ukuran : null,
            qty: (int) $line->qty,
            sortOrder: (int) $line->sort_order,
            availableStock: (int) $line->available_stock,
            defaultUnitPriceRupiah: (int) $line->harga_jual,
            minimumUnitPriceRupiah: (int) $line->harga_jual,
        );
    }

    private function applyPackageSearch(Builder $query, string $keyword): void
    {
        $rawKeyword = $keyword;
        $normalizedKeyword = $this->normalizeForSearch($keyword);

        $query->where(function (Builder $builder) use ($rawKeyword, $normalizedKeyword): void {
            $builder
                ->where('service_product_templates.id', 'like', '%' . $rawKeyword . '%')
                ->orWhere('service_catalog_items.name', 'like', '%' . $rawKeyword . '%')
                ->orWhere('service_catalog_items.normalized_name', 'like', '%' . $normalizedKeyword . '%')
                ->orWhereExists(function (Builder $exists) use ($rawKeyword, $normalizedKeyword): void {
                    $exists
                        ->select(DB::raw('1'))
                        ->from('service_product_template_lines')
                        ->join('products', 'products.id', '=', 'service_product_template_lines.product_id')
                        ->whereColumn(
                            'service_product_template_lines.service_product_template_id',
                            'service_product_templates.id'
                        )
                        ->whereNull('products.deleted_at')
                        ->where(function (Builder $productSearch) use ($rawKeyword, $normalizedKeyword): void {
                            $this->applyProductSearch($productSearch, $rawKeyword, $normalizedKeyword);
                        });
                })
                ->orWhereExists(function (Builder $exists) use ($rawKeyword, $normalizedKeyword): void {
                    $exists
                        ->select(DB::raw('1'))
                        ->from('products')
                        ->whereColumn('products.id', 'service_product_templates.product_id')
                        ->whereNull('products.deleted_at')
                        ->where(function (Builder $productSearch) use ($rawKeyword, $normalizedKeyword): void {
                            $this->applyProductSearch($productSearch, $rawKeyword, $normalizedKeyword);
                        });
                });
        });
    }

    private function applyProductSearch(Builder $query, string $rawKeyword, string $normalizedKeyword): void
    {
        $query
            ->where('products.kode_barang', 'like', '%' . $rawKeyword . '%')
            ->orWhere('products.nama_barang', 'like', '%' . $rawKeyword . '%')
            ->orWhere('products.merek', 'like', '%' . $rawKeyword . '%')
            ->orWhere('products.nama_barang_normalized', 'like', '%' . $normalizedKeyword . '%')
            ->orWhere('products.merek_normalized', 'like', '%' . $normalizedKeyword . '%');
    }

    private function boundedPackageLimit(int $limit): int
    {
        if ($limit < 1) {
            return ServiceProductTemplateLookupReaderPort::DEFAULT_PACKAGE_LIMIT;
        }

        return min($limit, ServiceProductTemplateLookupReaderPort::MAX_PACKAGE_LIMIT);
    }

    private function normalizeForSearch(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($normalized);
    }
}
