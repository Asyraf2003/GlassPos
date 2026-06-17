<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Support;

use App\Core\ProductCatalog\Product\Product;

final class SupplierInvoiceProductLabelBuilder
{
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
