<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Inventory\Services\RefundedStoreStockComponentTargets;
use App\Application\Payment\Services\PaymentComponentSelectionIds;
use App\Application\Shared\DTO\Result;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;

final class SelectedRowsRefundStockChoices
{
    public function __construct(private readonly RefundedStoreStockComponentTargets $targets) {}

    /** @param list<string> $selectedIds @param list<PaymentComponentAllocation> $allocations @param array<string, bool> $choices */
    public function validate(array $selectedIds, array $allocations, array $choices): ?Result
    {
        $required = [];
        foreach ($allocations as $allocation) {
            if (PaymentComponentSelectionIds::matches($allocation, $selectedIds)
                && $this->targets->supports($allocation->componentType())) {
                $required[$allocation->workItemId()] = true;
            }
        }
        if (array_diff_key($required, $choices) !== [] || array_diff_key($choices, $required) !== []
            || count(array_filter($choices, 'is_bool')) !== count($choices)) {
            return Result::failure('Pilih kembali/tidak kembali untuk stok pada setiap rincian terpilih.', [
                'stock_returns' => ['INVALID_STOCK_RETURN_CHOICES'],
            ]);
        }

        return null;
    }
}
