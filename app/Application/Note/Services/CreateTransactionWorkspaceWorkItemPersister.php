<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Core\Note\Note\Note;
use App\Ports\Out\Note\WorkItemWriterPort;

final class CreateTransactionWorkspaceWorkItemPersister
{
    public function __construct(
        private readonly WorkItemWriterPort $workItems,
        private readonly IssueInventoryOperation $issueInventory,
        private readonly WorkItemFactory $factory,
        private readonly CreateTransactionWorkspaceWorkItemPayloadMapper $mapper,
        private readonly CreateTransactionWorkspacePackageAllocationAuditMapper $packageAudits,
    ) {
    }

    /**
     * @param mixed $items
     */
    public function persist(Note $note, mixed $items, int $startLineNo = 1): CreateTransactionWorkspacePersistResult
    {
        if (! is_array($items)) {
            return new CreateTransactionWorkspacePersistResult(0, []);
        }

        $lineNo = max(1, $startLineNo);
        $created = 0;
        $packageAllocations = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            [$type, $sd, $ext, $sto] = $this->mapper->map($item);
            $workItem = $this->factory->build($note->id(), $lineNo, $type, $sd, $ext, $sto);

            $note->addWorkItem($workItem);
            $this->workItems->create($workItem);

            foreach ($workItem->storeStockLines() as $line) {
                $this->issueInventory->execute(
                    $line->productId(),
                    $line->qty(),
                    $note->transactionDate(),
                    'work_item_store_stock_line',
                    $line->id()
                );
            }

            $allocation = $this->packageAudits->from($item, $workItem);

            if ($allocation !== null) {
                $packageAllocations[] = $allocation;
            }

            $lineNo++;
            $created++;
        }

        return new CreateTransactionWorkspacePersistResult($created, $packageAllocations);
    }
}
