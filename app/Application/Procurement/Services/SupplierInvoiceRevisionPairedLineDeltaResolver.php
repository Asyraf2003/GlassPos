<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use DateTimeImmutable;

final class SupplierInvoiceRevisionPairedLineDeltaResolver
{
    public function __construct(
        private readonly SupplierInvoiceRevisionMovementFactory $movements,
    ) {
    }

    /**
     * @return list<InventoryMovement>
     */
    public function resolve(
        SupplierInvoiceLine $oldLine,
        SupplierInvoiceLine $newLine,
        DateTimeImmutable $movementDate,
    ): array {
        if ($oldLine->productId() !== $newLine->productId()) {
            return [
                $this->movements->stockOut($oldLine, $movementDate, $oldLine->qtyPcs()),
                $this->movements->stockIn($newLine, $movementDate, $newLine->qtyPcs()),
            ];
        }

        $deltaQty = $newLine->qtyPcs() - $oldLine->qtyPcs();
        $unitCostDelta = $newLine->unitCostRupiah()->amount() - $oldLine->unitCostRupiah()->amount();

        $movements = [];

        if ($unitCostDelta !== 0) {
            $movements[] = $this->movements->costRevaluation(
                $newLine,
                $movementDate,
                $unitCostDelta * $oldLine->qtyPcs(),
            );
        }

        return match (true) {
            $deltaQty > 0 => [...$movements, $this->movements->stockIn($newLine, $movementDate, $deltaQty)],
            $deltaQty < 0 => [...$movements, $this->movements->stockOut($newLine, $movementDate, abs($deltaQty))],
            default => $movements,
        };
    }
}
