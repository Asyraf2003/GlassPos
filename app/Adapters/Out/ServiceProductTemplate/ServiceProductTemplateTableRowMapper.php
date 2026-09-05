<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\Services\ServiceProductTemplatePackageSplitCalculator;

final class ServiceProductTemplateTableRowMapper
{
    public function __construct(private readonly ServiceProductTemplatePackageSplitCalculator $split) {}

    /** @return array<string, mixed> */
    public function map(object $row): array
    {
        $split = $this->split->calculate(
            (int) $row->harga_jual,
            (int) $row->default_service_price_rupiah,
            $row->default_package_total_rupiah !== null ? (int) $row->default_package_total_rupiah : null,
        );
        $id = (string) $row->id;

        return [
            'id' => $id,
            'product_id' => (string) $row->product_id,
            'service_catalog_item_id' => (string) $row->service_catalog_item_id,
            'kode_barang' => $row->kode_barang !== null ? (string) $row->kode_barang : '',
            'nama_barang' => (string) $row->nama_barang,
            'harga_jual' => (int) $row->harga_jual,
            'service_name' => (string) $row->service_name,
            'default_service_price_rupiah' => (int) $row->default_service_price_rupiah,
            'is_active' => (bool) $row->is_active,
        ] + $split + ['actions' => [
            'detail_url' => route('admin.service-product-templates.show', ['templateId' => $id]),
            'edit_url' => route('admin.service-product-templates.edit', ['templateId' => $id]),
            'product_url' => route('admin.products.show', ['productId' => (string) $row->product_id]),
            'service_url' => route('admin.services.edit', ['serviceId' => (string) $row->service_catalog_item_id]),
            'deactivate_url' => route('admin.service-product-templates.deactivate', ['templateId' => $id]),
            'reactivate_url' => route('admin.service-product-templates.reactivate', ['templateId' => $id]),
        ]];
    }
}
