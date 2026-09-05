<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateTableQuery;
use Illuminate\Database\Query\Builder;

final class ServiceProductTemplateTableOrderer
{
    public function apply(Builder $builder, ServiceProductTemplateTableQuery $query): void
    {
        $columns = [
            'product_name' => 'products.nama_barang',
            'service_name' => 'service_catalog_items.name',
            'default_service_price_rupiah' => 'service_product_templates.default_service_price_rupiah',
            'is_active' => 'service_product_templates.is_active',
        ];

        if ($query->sortBy === 'package_total') {
            $builder->orderByRaw(
                'COALESCE(service_product_templates.default_package_total_rupiah, products.harga_jual + service_product_templates.default_service_price_rupiah) '.$query->sortDir,
            );
        } elseif ($query->sortBy !== null) {
            $builder->orderBy($columns[$query->sortBy], $query->sortDir);
        } elseif ($query->q !== null) {
            $term = mb_strtolower($query->q);
            $builder->orderByRaw(
                'CASE WHEN LOWER(products.kode_barang) = ? THEN 0 WHEN LOWER(products.kode_barang) LIKE ? THEN 1 WHEN LOWER(products.nama_barang) = ? THEN 2 WHEN LOWER(products.nama_barang) LIKE ? THEN 3 WHEN LOWER(service_catalog_items.name) = ? THEN 4 WHEN LOWER(service_catalog_items.name) LIKE ? THEN 5 ELSE 6 END',
                [$term, $term.'%', $term, $term.'%', $term, $term.'%'],
            );
        }

        $builder->orderBy('products.nama_barang')
            ->orderBy('service_product_templates.sort_order')
            ->orderBy('service_product_templates.id');
    }
}
