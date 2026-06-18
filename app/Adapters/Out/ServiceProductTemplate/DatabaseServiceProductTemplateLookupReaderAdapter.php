<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
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
}
