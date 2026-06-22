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
        foreach ($movements as $m) {
            $pId = $m->productId();
            if (!isset($state[$pId])) $state[$pId] = ['qty' => 0, 'value' => 0];

            if ($m->movementType() === 'stock_in') {
                $state[$pId]['qty'] += $m->qtyDelta();
                $state[$pId]['value'] += $m->totalCostRupiah()->amount();
            } elseif ($m->movementType() === 'stock_out' && $state[$pId]['qty'] > 0) {
                $issue = abs($m->qtyDelta());
                $state[$pId]['qty'] -= $issue;
                $state[$pId]['value'] -= abs($m->totalCostRupiah()->amount());
                if ($state[$pId]['value'] < 0) $state[$pId]['value'] = 0;
            } elseif ($m->movementType() === 'cost_revaluation') {
                $state[$pId]['value'] += $m->totalCostRupiah()->amount();
                if ($state[$pId]['value'] < 0) $state[$pId]['value'] = 0;
            }
        }

        ksort($state);
        $result = [];
        foreach ($state as $pId => $s) {
            if ($s['qty'] <= 0) continue;
            $val = Money::fromInt($s['value']);
            $avg = Money::fromInt(intdiv($s['value'], $s['qty']));
            $result[] = ProductInventoryCosting::create($pId, $avg, $val);
        }
        return $result;
    }
}
