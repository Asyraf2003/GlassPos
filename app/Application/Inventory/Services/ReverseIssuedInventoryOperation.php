<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\InventoryMovementWriterPort;
use App\Ports\Out\Inventory\ProductInventoryCostingReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingWriterPort;
use App\Ports\Out\Inventory\ProductInventoryReaderPort;
use App\Ports\Out\Inventory\ProductInventoryWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class ReverseIssuedInventoryOperation
{
    public function __construct(
        private readonly InventoryMovementReaderPort $movements,
        private readonly InventoryMovementWriterPort $movementWriter,
        private readonly ProductInventoryReaderPort $inventories,
        private readonly ProductInventoryWriterPort $inventoryWriter,
        private readonly ProductInventoryCostingReaderPort $costings,
        private readonly ProductInventoryCostingWriterPort $costingWriter,
        private readonly UuidPort $uuid,
    ) {
    }

    /**
     * @return list<InventoryMovement>
     */
    public function execute(string $sourceType, string $sourceId, DateTimeImmutable $date, string $reverseSourceType): array
    {
        $normalizedSourceType = trim($sourceType);
        $normalizedSourceId = trim($sourceId);
        $normalizedReverseSourceType = trim($reverseSourceType);

        foreach ($this->movements->getBySource($normalizedReverseSourceType, $normalizedSourceId) as $existingReverse) {
            if ($existingReverse->qtyDelta() > 0) {
                return [];
            }
        }

        $reversed = [];

        foreach ($this->movements->getBySource($normalizedSourceType, $normalizedSourceId) as $movement) {
            if ($movement->qtyDelta() >= 0) {
                continue;
            }

            $qty = abs($movement->qtyDelta());
            $inventory = $this->inventories->getByProductIdForUpdate($movement->productId());
            $costing = $this->costings->getByProductId($movement->productId());

            if ($inventory === null || $costing === null) {
                continue;
            }

            $existingQty = $inventory->qtyOnHand();
            $inventory->increase($qty);
            $costing->applyIncomingStock(
                $existingQty,
                $qty,
                Money::fromInt($qty * $movement->unitCostRupiah()->amount())
            );

            $reverseMovement = InventoryMovement::create(
                $this->uuid->generate(),
                $movement->productId(),
                'stock_in',
                $normalizedReverseSourceType,
                $normalizedSourceId,
                $date,
                $qty,
                $movement->unitCostRupiah()
            );

            $this->inventoryWriter->upsert($inventory);
            $this->costingWriter->upsert($costing);
            $reversed[] = $reverseMovement;
        }

        $this->movementWriter->createMany($reversed);

        return $reversed;
    }
}
