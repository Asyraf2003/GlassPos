<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

final class ServiceProductTemplateShowTemplateMapper
{
    public function __construct(private readonly ServiceProductTemplatePackageSplitCalculator $split)
    {
    }

    /** @return array<string, mixed> */
    public function map(object $row): array
    {
        $productPrice = (int) $row->harga_jual;
        $templateServicePrice = (int) $row->default_service_price_rupiah;
        $averageCost = $this->nullableInt($row->avg_cost_rupiah);

        return [
            'id' => (string) $row->id,
            'product_id' => (string) $row->product_id,
            'service_catalog_item_id' => (string) $row->service_catalog_item_id,
            'product_name' => (string) $row->nama_barang,
            'product_code' => $this->optionalString($row->kode_barang),
            'product_brand' => $this->optionalString($row->merek),
            'product_size' => $this->optionalString($row->ukuran),
            'product_price' => $productPrice,
            'average_cost' => $averageCost,
            'inventory_value' => $this->nullableInt($row->inventory_value_rupiah),
            'product_gross_margin' => $averageCost !== null ? $productPrice - $averageCost : null,
            'service_name' => (string) $row->service_name,
            'template_service_price' => $templateServicePrice,
            'current_service_price' => (int) $row->current_service_price_rupiah,
            'service_is_active' => (bool) $row->service_is_active,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ] + $this->split->calculate(
            $productPrice,
            $templateServicePrice,
            $this->nullableInt($row->default_package_total_rupiah)
        );
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    private function optionalString(mixed $value): string
    {
        return $value !== null && (string) $value !== '' ? (string) $value : '-';
    }
}
