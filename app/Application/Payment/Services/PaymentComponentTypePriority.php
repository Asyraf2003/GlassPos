<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\Exceptions\DomainException;

final class PaymentComponentTypePriority
{
    public static function forType(string $componentType): int
    {
        return match ($componentType) {
            PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART => 1,
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            PaymentComponentType::SERVICE_STORE_STOCK_PART => 2,
            PaymentComponentType::SERVICE_FEE => 3,
            default => throw new DomainException('Payment component type tidak valid untuk prioritas alokasi.'),
        };
    }
}
