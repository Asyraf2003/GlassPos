<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use App\Application\ServiceProductTemplate\Services\ServiceProductTemplatePackageSplitCalculator;
use Illuminate\Support\Facades\DB;

final class LinkedServicePackagesForProduct
{
    public function __construct(private readonly ServiceProductTemplatePackageSplitCalculator $split)
    {
    }

    /** @return list<array<string, mixed>> */
    public function get(string $productId): array
    {
        return DB::table('service_product_templates')
            ->join('products', 'products.id', '=', 'service_product_templates.product_id')
            ->join('service_catalog_items', 'service_catalog_items.id', '=', 'service_product_templates.service_catalog_item_id')
            ->leftJoin('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->where('service_product_templates.product_id', trim($productId))
            ->select($this->columns())
            ->orderByDesc('service_product_templates.is_active')
            ->orderBy('service_catalog_items.name')
            ->orderBy('service_product_templates.id')
            ->get()
            ->map(fn (object $row): array => $this->row($row))
            ->all();
    }

    /** @return list<string> */
    private function columns(): array
    {
        return [
            'service_product_templates.id',
            'service_product_templates.product_id',
            'service_product_templates.service_catalog_item_id',
            'service_product_templates.default_service_price_rupiah',
            'service_product_templates.default_package_total_rupiah',
            'service_product_templates.is_active',
            'products.nama_barang',
            'products.harga_jual',
            'product_inventory_costing.avg_cost_rupiah',
            'product_inventory_costing.inventory_value_rupiah',
            'service_catalog_items.name as service_name',
        ];
    }

    /** @return array<string, mixed> */
    private function row(object $row): array
    {
        $productPrice = (int) $row->harga_jual;
        $servicePrice = (int) $row->default_service_price_rupiah;
        $averageCost = $row->avg_cost_rupiah !== null ? (int) $row->avg_cost_rupiah : null;
        $split = $this->split->calculate($productPrice, $servicePrice, $this->nullableInt($row->default_package_total_rupiah));

        return [
            'id' => (string) $row->id,
            'product_id' => (string) $row->product_id,
            'service_catalog_item_id' => (string) $row->service_catalog_item_id,
            'product_name' => (string) $row->nama_barang,
            'service_name' => (string) $row->service_name,
            'product_price' => $productPrice,
            'average_cost' => $averageCost,
            'inventory_value' => $this->nullableInt($row->inventory_value_rupiah),
            'product_gross_margin' => $averageCost !== null ? $productPrice - $averageCost : null,
            'service_price' => $servicePrice,
            'is_active' => (bool) $row->is_active,
        ] + $split;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }
}
