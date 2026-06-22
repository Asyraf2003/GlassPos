<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;

final class ExistingPaymentComponentTotals
{
    /**
     * @return array<string, int>
     */
    public static function build(
        PaymentComponentAllocationReaderPort $reader,
        string $noteId,
        ?RefundComponentAllocationReaderPort $refunds = null,
    ): array {
        $totals = [];

        foreach ($reader->listByNoteId($noteId) as $allocation) {
            $key = self::key($allocation->componentType(), $allocation->componentRefId());
            $totals[$key] = ($totals[$key] ?? 0) + $allocation->allocatedAmountRupiah()->amount();
        }

        if ($refunds !== null) {
            foreach ($refunds->listByNoteId($noteId) as $refund) {
                $key = self::key($refund->componentType(), $refund->componentRefId());
                $totals[$key] = max(($totals[$key] ?? 0) - $refund->refundedAmountRupiah()->amount(), 0);
            }
        }

        return $totals;
    }

    public static function key(string $componentType, string $componentRefId): string
    {
        return $componentType . '|' . $componentRefId;
    }
}
