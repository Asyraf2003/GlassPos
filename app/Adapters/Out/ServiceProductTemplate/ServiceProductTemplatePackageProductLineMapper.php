<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;

final class ServiceProductTemplatePackageProductLineMapper
{
    public function map(object $line): ServiceProductTemplatePackageProductLineRow
    {
        return new ServiceProductTemplatePackageProductLineRow(
            productId: (string) $line->product_id,
            kodeBarang: $line->kode_barang !== null ? (string) $line->kode_barang : null,
            productName: (string) $line->nama_barang,
            brand: (string) $line->merek,
            size: $line->ukuran !== null ? (int) $line->ukuran : null,
            qty: (int) $line->qty,
            sortOrder: (int) $line->sort_order,
            availableStock: (int) $line->available_stock,
            defaultUnitPriceRupiah: (int) $line->harga_jual,
            minimumUnitPriceRupiah: (int) $line->harga_jual,
        );
    }
}
