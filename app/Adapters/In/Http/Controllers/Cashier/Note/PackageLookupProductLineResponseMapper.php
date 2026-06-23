<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;

final class PackageLookupProductLineResponseMapper
{
    /** @return array<string, mixed> */
    public function map(ServiceProductTemplatePackageProductLineRow $line): array
    {
        return [
            'product_id' => $line->productId,
            'label' => $line->label(),
            'product_name' => $line->productName,
            'kode_barang' => $line->kodeBarang,
            'qty' => $line->qty,
            'sort_order' => $line->sortOrder,
            'available_stock' => $line->availableStock,
            'unit_price_rupiah' => $line->defaultUnitPriceRupiah,
            'minimum_unit_price_rupiah' => $line->minimumUnitPriceRupiah,
            'stock_status' => $line->availableStock >= $line->qty ? 'safe' : 'insufficient',
        ];
    }
}
