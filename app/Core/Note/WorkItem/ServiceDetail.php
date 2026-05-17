<?php

declare(strict_types=1);

namespace App\Core\Note\WorkItem;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class ServiceDetail
{
    public const PART_SOURCE_NONE = 'none';
    public const PART_SOURCE_CUSTOMER_OWNED = 'customer_owned';

    private function __construct(
        private string $serviceName,
        private Money $servicePriceRupiah,
        private string $partSource,
    ) {
    }

    public static function create(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
    ): self {
        self::assertValid($serviceName, $servicePriceRupiah, $partSource);

        return new self(
            trim($serviceName),
            $servicePriceRupiah,
            trim($partSource),
        );
    }

    public static function rehydrate(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
    ): self {
        self::assertValid($serviceName, $servicePriceRupiah, $partSource);

        return new self(
            trim($serviceName),
            $servicePriceRupiah,
            trim($partSource),
        );
    }

    public function serviceName(): string
    {
        return $this->serviceName;
    }

    public function servicePriceRupiah(): Money
    {
        return $this->servicePriceRupiah;
    }

    public function partSource(): string
    {
        return $this->partSource;
    }

    private static function assertValid(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
    ): void {
        if (trim($serviceName) === '') {
            throw new DomainException('Service name wajib ada.');
        }

        if ($servicePriceRupiah->amount() < 0) {
            throw new DomainException('Service price rupiah tidak boleh negatif.');
        }

        $normalizedPartSource = trim($partSource);

        if (in_array(
            $normalizedPartSource,
            [
                self::PART_SOURCE_NONE,
                self::PART_SOURCE_CUSTOMER_OWNED,
            ],
            true
        ) === false) {
            throw new DomainException('Part source pada service detail tidak valid.');
        }
    }
}
