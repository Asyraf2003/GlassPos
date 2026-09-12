<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ProductCatalog\DTO\ProductLookupRow;

trait DatabaseServiceProductTemplateAdminProductOptions
{
    /** @param list<string> $ids
     * @return list<array<string, mixed>>
     */
    public function productOptions(array $ids = []): array
    {
        return array_map(static fn (ProductLookupRow $row): array => [
            'id' => $row->id,
            'code' => $row->kodeBarang,
            'name' => $row->namaBarang,
            'brand' => $row->merek,
            'size' => $row->ukuran,
            'available_stock' => $row->availableStock,
            'price_rupiah' => $row->defaultUnitPriceRupiah,
            'label' => $row->label(),
        ], $this->products->findByIds($ids));
    }
}
