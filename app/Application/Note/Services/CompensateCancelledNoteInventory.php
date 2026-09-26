<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Inventory\Services\ReverseIssuedInventoryOperation;
use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingReaderPort;
use App\Ports\Out\Inventory\ProductInventoryReaderPort;
use DateTimeImmutable;

final class CompensateCancelledNoteInventory
{
    public function __construct(
        private readonly InventoryMovementReaderPort $movements,
        private readonly ProductInventoryReaderPort $inventories,
        private readonly ProductInventoryCostingReaderPort $costings,
        private readonly ReverseIssuedInventoryOperation $reverse,
    ) {}

    /** @return list<string> */
    public function execute(Note $note, DateTimeImmutable $date): array
    {
        $ids = [];
        foreach ($note->workItems() as $item) {
            foreach ($item->storeStockLines() as $line) {
                $issued = $this->movements->getBySource('work_item_store_stock_line', $line->id());
                if (count($issued) !== 1 || $issued[0]->qtyDelta() !== -$line->qty()
                    || $issued[0]->productId() !== $line->productId()) {
                    throw new DomainException('CANCELLATION_INVENTORY_INCONSISTENT');
                }
                if ($this->movements->getBySource('transaction_workspace_updated', $line->id()) !== []) {
                    throw new DomainException('CANCELLATION_INVENTORY_INCONSISTENT');
                }
                if ($this->inventories->getByProductIdForUpdate($line->productId()) === null
                    || $this->costings->getByProductId($line->productId()) === null) {
                    throw new DomainException('CANCELLATION_INVENTORY_INCOMPLETE');
                }
                $this->reverse->execute('work_item_store_stock_line', $line->id(), $date, 'work_item_store_stock_line_reversal');
                $returns = $this->movements->getBySource('work_item_store_stock_line_reversal', $line->id());
                if (count($returns) !== 1 || $returns[0]->qtyDelta() !== $line->qty()
                    || $returns[0]->productId() !== $line->productId()
                    || ! $returns[0]->unitCostRupiah()->equals($issued[0]->unitCostRupiah())) {
                    throw new DomainException('CANCELLATION_INVENTORY_INCONSISTENT');
                }
                $ids[] = $returns[0]->id();
            }
        }

        return $ids;
    }
}
