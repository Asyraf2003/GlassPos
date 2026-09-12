<?php

declare(strict_types=1);

namespace App\Adapters\Out\ProductCatalog;

use App\Application\ProductCatalog\DTO\ProductLookupRow;

final class ProductLookupRowMapper
{
    public function map(object $row): ProductLookupRow
    {
        return new ProductLookupRow(
            id: (string) $row->id,
            kodeBarang: $row->kode_barang !== null ? (string) $row->kode_barang : null,
            namaBarang: (string) $row->nama_barang,
            merek: (string) $row->merek,
            ukuran: $row->ukuran !== null ? (int) $row->ukuran : null,
            availableStock: (int) $row->available_stock,
            defaultUnitPriceRupiah: (int) $row->harga_jual,
            minimumUnitPriceRupiah: (int) $row->harga_jual,
        );
    }
}
