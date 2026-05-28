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

        $lines = $this->lines($item['product_lines'] ?? []);
        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');
        $sparepartTotal = 0;
        $normalizedLines = [];

        if ($lines === []) {
            throw new DomainException('Product wajib dipilih.');
        }

        foreach ($lines as $line) {
            $productId = $this->requiredString($line['product_id'] ?? null, 'Product wajib dipilih.');
            $qty = $this->requiredInt($line['qty'] ?? null, 'Qty produk wajib diisi.');

            $product = $this->products->getById($productId)
                ?? throw new DomainException('Product tidak ditemukan.');

            $productUnitPrice = $product->hargaJual()->amount();
            $sparepartTotal += $productUnitPrice * $qty;

            $line['product_id'] = $productId;
            $line['qty'] = $qty;
            $line['unit_price_rupiah'] = $productUnitPrice;

            $normalizedLines[] = $line;
        }

        if ($packageTotal < $sparepartTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total harga sparepart.');
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $service['price_rupiah'] = $packageTotal - $sparepartTotal;

        $item['service'] = $service;
        $item['product_lines'] = $normalizedLines;

        return $item;
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private function lines(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($this->looksLikeLine($value)) {
            return [$value];
        }

        $lines = [];

        foreach (array_values($value) as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param array<mixed> $value
     */
    private function looksLikeLine(array $value): bool
    {
        return array_key_exists('product_id', $value)
            || array_key_exists('qty', $value)
            || array_key_exists('unit_price_rupiah', $value)
            || array_key_exists('price_basis', $value);
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
