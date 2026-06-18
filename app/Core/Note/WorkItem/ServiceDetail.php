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
        private Money $packageProfitRupiah,
        private ?Money $packageBaseServicePriceRupiah,
        private Money $packageServiceExtraRupiah,
    ) {
    }

    public static function create(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
        ?Money $packageProfitRupiah = null,
        ?Money $packageBaseServicePriceRupiah = null,
        ?Money $packageServiceExtraRupiah = null,
    ): self {
        $profit = $packageProfitRupiah ?? Money::zero();
        $extra = $packageServiceExtraRupiah ?? Money::zero();

        self::assertValid($serviceName, $servicePriceRupiah, $partSource, $profit, $packageBaseServicePriceRupiah, $extra);

        return new self(
            trim($serviceName),
            $servicePriceRupiah,
            trim($partSource),
            $profit,
            $packageBaseServicePriceRupiah,
            $extra,
        );
    }

    public static function rehydrate(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
        ?Money $packageProfitRupiah = null,
        ?Money $packageBaseServicePriceRupiah = null,
        ?Money $packageServiceExtraRupiah = null,
    ): self {
        return self::create(
            $serviceName,
            $servicePriceRupiah,
            $partSource,
            $packageProfitRupiah,
            $packageBaseServicePriceRupiah,
            $packageServiceExtraRupiah,
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

    public function packageProfitRupiah(): Money
    {
        return $this->packageProfitRupiah;
    }

    public function packageBaseServicePriceRupiah(): ?Money
    {
        return $this->packageBaseServicePriceRupiah;
    }

    public function packageServiceExtraRupiah(): Money
    {
        return $this->packageServiceExtraRupiah;
    }

    public function totalPriceRupiah(): Money
    {
        return $this->servicePriceRupiah->add($this->packageProfitRupiah);
    }

    public function partSource(): string
    {
        return $this->partSource;
    }

    private static function assertValid(
        string $serviceName,
        Money $servicePriceRupiah,
        string $partSource,
        Money $packageProfitRupiah,
        ?Money $packageBaseServicePriceRupiah,
        Money $packageServiceExtraRupiah,
    ): void {
        if (trim($serviceName) === '') {
            throw new DomainException('Service name wajib ada.');
        }

        if ($servicePriceRupiah->amount() < 0) {
            throw new DomainException('Service price rupiah tidak boleh negatif.');
        }

        if ($packageProfitRupiah->amount() < 0) {
            throw new DomainException('Package profit rupiah tidak boleh negatif.');
        }

        if ($packageBaseServicePriceRupiah !== null && $packageBaseServicePriceRupiah->amount() < 0) {
            throw new DomainException('Package base service price rupiah tidak boleh negatif.');
        }

        if ($packageServiceExtraRupiah->amount() < 0) {
            throw new DomainException('Package service extra rupiah tidak boleh negatif.');
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
