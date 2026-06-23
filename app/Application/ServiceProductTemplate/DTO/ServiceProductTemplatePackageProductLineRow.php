<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\DTO;

final readonly class ServiceProductTemplatePackageProductLineRow
{
    public function __construct(
        public string $productId,
        public ?string $kodeBarang,
        public string $productName,
        public string $brand,
        public ?int $size,
        public int $qty,
        public int $sortOrder,
        public int $availableStock,
        public int $defaultUnitPriceRupiah,
        public int $minimumUnitPriceRupiah,
    ) {
    }

    public function label(): string
    {
        $parts = [
            $this->productName,
            $this->brand,
        ];

        if ($this->size !== null) {
            $parts[] = (string) $this->size;
        }

        $label = implode(' — ', $parts);

        if ($this->kodeBarang !== null) {
            $label .= ' (' . $this->kodeBarang . ')';
        }

        return $label;
    }
}
