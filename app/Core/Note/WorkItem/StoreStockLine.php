<?php

declare(strict_types=1);

namespace App\Core\Note\WorkItem;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class StoreStockLine
{
    private function __construct(
        private string $id,
        private string $productId,
        private int $qty,
        private Money $lineTotalRupiah,
    ) {
    }

    public static function create(
        string $id,
        string $productId,
        int $qty,
        Money $lineTotalRupiah,
    ): self {
        self::assertValid($id, $productId, $qty, $lineTotalRupiah);

        return new self(
            trim($id),
            trim($productId),
            $qty,
            $lineTotalRupiah,
        );
    }

    public static function rehydrate(
        string $id,
        string $productId,
        int $qty,
        Money $lineTotalRupiah,
    ): self {
        self::assertValid($id, $productId, $qty, $lineTotalRupiah);

        return new self(
            trim($id),
            trim($productId),
            $qty,
            $lineTotalRupiah,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function qty(): int
    {
        return $this->qty;
    }

    public function lineTotalRupiah(): Money
    {
        return $this->lineTotalRupiah;
    }

    private static function assertValid(
        string $id,
        string $productId,
        int $qty,
        Money $lineTotalRupiah,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Store stock line id wajib ada.');
        }

        if (trim($productId) === '') {
            throw new DomainException('Product id pada store stock line wajib ada.');
        }

        if ($qty <= 0) {
            throw new DomainException('Qty pada store stock line harus lebih besar dari nol.');
        }

        if ($lineTotalRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Line total rupiah pada store stock line harus lebih besar dari nol.');
        }
    }
}
