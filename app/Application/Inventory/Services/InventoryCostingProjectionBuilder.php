<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\ValueObjects\Money;

final class InventoryCostingProjectionBuilder
{
    /**
     * @param list<InventoryMovement> $movements
     * @return list<ProductInventoryCosting>
     */
    public function build(array $movements): array
    {
        $state = [];

        foreach ($movements as $movement) {
            $productId = $movement->productId();

            if (! isset($state[$productId])) {
                $state[$productId] = [
                    'qty' => 0,
                    'value' => 0,
                ];
            }

            $state[$productId]['qty'] += $movement->qtyDelta();
            $state[$productId]['value'] += $movement->totalCostRupiah()->amount();
        }

        ksort($state);

        $result = [];

        foreach ($state as $productId => $row) {
            $qty = (int) $row['qty'];
            $value = max(0, (int) $row['value']);

            if ($qty <= 0) {
                continue;
            }

            $result[] = ProductInventoryCosting::create(
                $productId,
                Money::fromInt(intdiv($value, $qty)),
                Money::fromInt($value),
            );
        }

        return $result;
    }
}
