<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note\WorkItem;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class ServiceDetailTest extends TestCase
{
    public function test_it_creates_service_detail_with_customer_owned_part_source(): void
    {
        $detail = ServiceDetail::create(
            'Servis Karburator',
            Money::fromInt(50000),
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
        );

        $this->assertSame('Servis Karburator', $detail->serviceName());
        $this->assertSame(50000, $detail->servicePriceRupiah()->amount());
        $this->assertSame(ServiceDetail::PART_SOURCE_CUSTOMER_OWNED, $detail->partSource());
    }

    public function test_it_rejects_invalid_part_source(): void
    {
        $this->expectException(DomainException::class);

        ServiceDetail::create(
            'Servis Karburator',
            Money::fromInt(50000),
            'store_stock',
        );
    }

    public function test_it_accepts_zero_service_price(): void
    {
        $detail = ServiceDetail::create(
            'Servis Karburator',
            Money::zero(),
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->assertSame(0, $detail->servicePriceRupiah()->amount());
    }

    public function test_it_rejects_negative_service_price(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Service price rupiah tidak boleh negatif.');

        ServiceDetail::create(
            'Servis Karburator',
            Money::fromInt(-1),
            ServiceDetail::PART_SOURCE_NONE,
        );
    }
}
