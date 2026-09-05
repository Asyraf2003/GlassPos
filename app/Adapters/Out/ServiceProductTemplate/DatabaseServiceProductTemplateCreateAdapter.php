<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateCreatePort;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplateCreateAdapter implements ServiceProductTemplateCreatePort
{
    public function __construct(private readonly DatabaseServiceProductTemplateLineWriter $lineWriter) {}

    public function activeTemplateExists(string $productId, string $serviceCatalogItemId): bool
    {
        return DB::table('service_product_templates')
            ->where('product_id', trim($productId))
            ->where('service_catalog_item_id', trim($serviceCatalogItemId))
            ->where('is_active', true)
            ->exists();
    }

    public function serviceDefaultPriceRupiah(string $serviceCatalogItemId): ?int
    {
        $value = DB::table('service_catalog_items')
            ->where('id', trim($serviceCatalogItemId))
            ->where('is_active', true)
            ->value('default_price_rupiah');

        return $value === null ? null : (int) $value;
    }

    public function productLinesTotalRupiah(array $lines): ?int
    {
        $total = 0;

        foreach ($lines as $line) {
            $price = DB::table('products')
                ->where('id', trim($line['product_id']))
                ->whereNull('deleted_at')
                ->value('harga_jual');

            if ($price === null) {
                return null;
            }

            $total += ((int) $price) * $line['qty'];
        }

        return $total;
    }

    public function create(
        string $templateId,
        string $serviceCatalogItemId,
        int $servicePriceRupiah,
        int $packageTotalRupiah,
        array $lines,
    ): void {
        DB::table('service_product_templates')->insert([
            'id' => $templateId,
            'product_id' => $lines[0]['product_id'],
            'service_catalog_item_id' => $serviceCatalogItemId,
            'default_service_price_rupiah' => $servicePriceRupiah,
            'default_package_total_rupiah' => $packageTotalRupiah,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->lineWriter->replace($templateId, $lines);
    }
}
