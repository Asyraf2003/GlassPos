<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer
{
    public function __construct(
        private readonly ProductReaderPort $products,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function compose(array $item): array
    {
        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            return $item;
        }

        $line = $this->firstLine($item['product_lines'] ?? []);
        $productId = $this->requiredString($line['product_id'] ?? null, 'Product wajib dipilih.');
        $qty = $this->requiredInt($line['qty'] ?? null, 'Qty produk wajib diisi.');
        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');

        $product = $this->products->getById($productId)
            ?? throw new DomainException('Product tidak ditemukan.');

        $productUnitPrice = $product->hargaJual()->amount();
        $sparepartTotal = $productUnitPrice * $qty;

        if ($packageTotal < $sparepartTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total harga sparepart.');
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $service['price_rupiah'] = $packageTotal - $sparepartTotal;

        $line['unit_price_rupiah'] = $productUnitPrice;

        $item['service'] = $service;
        $item['product_lines'] = [$line];

        return $item;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function firstLine(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first) ? $first : [];
    }

    private function requiredString(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException($message);
        }

        return trim($value);
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }
}
