<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Support;

use App\Core\ProductCatalog\Product\Product;

final class SupplierInvoiceProductLabelBuilder
{
    /** @param array<string, mixed> $line */
    public function historical(array $line): string
    {
        $parts = [(string) ($line['nama_barang'] ?? ''), (string) ($line['merek'] ?? '')];
        if (($line['ukuran'] ?? null) !== null) {
            $parts[] = (string) $line['ukuran'];
        }
        $label = implode(' - ', $parts);
        if (($line['kode_barang'] ?? '') !== '') {
            $label .= ' (' . $line['kode_barang'] . ')';
        }
        return $label;
    }

    public function build(Product $product, string $separator = ' - '): string
    {
        $parts = [$product->namaBarang(), $product->merek()];

        if ($product->ukuran() !== null) {
            $parts[] = (string) $product->ukuran();
        }

        $label = implode($separator, $parts);

        if ($product->kodeBarang() !== null) {
            $label .= ' (' . $product->kodeBarang() . ')';
        }

        return $label;
    }
}
