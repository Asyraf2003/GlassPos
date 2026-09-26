<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;

final class RefundComponentTypePolicy
{
    public static function isDefaultRefundable(string $componentType): bool
    {
        return in_array(trim($componentType), [
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
        ], true);
    }

    public static function isSelectedRowRefundable(string $componentType): bool
    {
        return self::isDefaultRefundable($componentType)
            || trim($componentType) === PaymentComponentType::SERVICE_FEE;
    }
}
