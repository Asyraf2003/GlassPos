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

        if (! $this->hasProductLine($item['product_lines'] ?? [])) {
            return $item;
        }

        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');
        $pricedLines = (new CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer($this->products))
            ->compose($item['product_lines'] ?? []);
        $sparepartTotal = $pricedLines['sparepart_total_rupiah'];

        if ($packageTotal < $sparepartTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total harga sparepart.');
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $service['price_rupiah'] = $packageTotal - $sparepartTotal;

        $item['service'] = $service;
        $item['product_lines'] = $pricedLines['product_lines'];

        return $item;
    }

    private function hasProductLine(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first)
            && is_string($first['product_id'] ?? null)
            && trim((string) $first['product_id']) !== '';
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }
}
