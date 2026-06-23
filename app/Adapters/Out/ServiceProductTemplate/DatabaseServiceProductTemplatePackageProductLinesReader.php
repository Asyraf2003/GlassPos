<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DatabaseServiceProductTemplatePackageProductLinesReader
{
    public function __construct(private readonly ServiceProductTemplatePackageProductLineMapper $mapper)
    {
    }

    /**
     * @return list<ServiceProductTemplatePackageProductLineRow>
     */
    public function forTemplate(string $templateId, string $legacyProductId): array
    {
        $rows = $this->lineRows($templateId);

        if ($rows->isEmpty()) {
            $rows = $this->legacyLineRows($legacyProductId);
        }

        return array_map(
            fn (object $line): ServiceProductTemplatePackageProductLineRow => $this->mapper->map($line),
            $rows->all(),
        );
    }

    /** @return Collection<int, object> */
    private function lineRows(string $templateId): Collection
    {
        return DB::table('service_product_template_lines')
            ->join('products', 'products.id', '=', 'service_product_template_lines.product_id')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->where('service_product_template_lines.service_product_template_id', $templateId)
            ->whereNull('products.deleted_at')
            ->select($this->selectColumns())
            ->orderBy('service_product_template_lines.sort_order')
            ->orderBy('products.nama_barang')
            ->limit(3)
            ->get();
    }

    /** @return Collection<int, object> */
    private function legacyLineRows(string $legacyProductId): Collection
    {
        return DB::table('products')
            ->leftJoin('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->where('products.id', $legacyProductId)
            ->whereNull('products.deleted_at')
            ->select($this->selectColumns(true))
            ->get();
    }

    /** @return list<mixed> */
    private function selectColumns(bool $legacy = false): array
    {
        return [
            'products.id as product_id',
            'products.kode_barang',
            'products.nama_barang',
            'products.merek',
            'products.ukuran',
            'products.harga_jual',
            $legacy ? DB::raw('1 as qty') : 'service_product_template_lines.qty',
            $legacy ? DB::raw('0 as sort_order') : 'service_product_template_lines.sort_order',
            DB::raw('COALESCE(product_inventory.qty_on_hand, 0) as available_stock'),
        ];
    }
}
