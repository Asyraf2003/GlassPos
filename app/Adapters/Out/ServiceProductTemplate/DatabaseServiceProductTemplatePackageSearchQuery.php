<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplatePackageSearchQuery
{
    /** @return Collection<int, object> */
    public function search(string $query, int $limit): Collection
    {
        $trimmedQuery = trim($query);

        if (mb_strlen($trimmedQuery) < 2) {
            return collect();
        }

        $builder = DB::table('service_product_templates')
            ->join('service_catalog_items', 'service_catalog_items.id', '=', 'service_product_templates.service_catalog_item_id')
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

        $this->applySearch($builder, $trimmedQuery);

        return $builder
            ->orderBy('service_product_templates.sort_order')
            ->orderBy('service_catalog_items.name')
            ->orderBy('service_product_templates.id')
            ->limit($this->boundedLimit($limit))
            ->get();
    }

    private function applySearch(Builder $query, string $keyword): void
    {
        $raw = $keyword;
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($keyword)) ?? trim($keyword));

        $query->where(function (Builder $builder) use ($raw, $normalized): void {
            $builder
                ->where('service_product_templates.id', 'like', '%' . $raw . '%')
                ->orWhere('service_catalog_items.name', 'like', '%' . $raw . '%')
                ->orWhere('service_catalog_items.normalized_name', 'like', '%' . $normalized . '%')
                ->orWhereExists(fn (Builder $exists) => $this->productLineExists($exists, $raw, $normalized))
                ->orWhereExists(fn (Builder $exists) => $this->legacyProductExists($exists, $raw, $normalized));
        });
    }

    private function productLineExists(Builder $exists, string $raw, string $normalized): void
    {
        $exists->select(DB::raw('1'))
            ->from('service_product_template_lines')
            ->join('products', 'products.id', '=', 'service_product_template_lines.product_id')
            ->whereColumn('service_product_template_lines.service_product_template_id', 'service_product_templates.id')
            ->whereNull('products.deleted_at')
            ->where(fn (Builder $products) => $this->applyProductSearch($products, $raw, $normalized));
    }

    private function legacyProductExists(Builder $exists, string $raw, string $normalized): void
    {
        $exists->select(DB::raw('1'))
            ->from('products')
            ->whereColumn('products.id', 'service_product_templates.product_id')
            ->whereNull('products.deleted_at')
            ->where(fn (Builder $products) => $this->applyProductSearch($products, $raw, $normalized));
    }

    private function applyProductSearch(Builder $query, string $raw, string $normalized): void
    {
        $query->where('products.kode_barang', 'like', '%' . $raw . '%')
            ->orWhere('products.nama_barang', 'like', '%' . $raw . '%')
            ->orWhere('products.merek', 'like', '%' . $raw . '%')
            ->orWhere('products.nama_barang_normalized', 'like', '%' . $normalized . '%')
            ->orWhere('products.merek_normalized', 'like', '%' . $normalized . '%');
    }

    private function boundedLimit(int $limit): int
    {
        return $limit < 1
            ? ServiceProductTemplateLookupReaderPort::DEFAULT_PACKAGE_LIMIT
            : min($limit, ServiceProductTemplateLookupReaderPort::MAX_PACKAGE_LIMIT);
    }
}
