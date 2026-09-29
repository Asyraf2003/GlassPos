<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Application\Inventory\Services\InventoryCostingProjectionBuilder;
use App\Ports\Out\Reporting\InventoryMovementReportingSourceReaderPort;

final class DatabaseInventoryMovementReportingSourceReaderAdapter implements InventoryMovementReportingSourceReaderPort
{
    public function getInventoryMovementSummaryRows(
        string $fromMutationDate,
        string $toMutationDate,
    ): array {
        return InventoryMovementSummaryDatabaseQuery::get($fromMutationDate, $toMutationDate);
    }

    public function getInventoryMovementSummaryReconciliation(
        string $fromMutationDate,
        string $toMutationDate,
    ): array {
        return InventoryMovementReconciliationDatabaseQuery::get($fromMutationDate, $toMutationDate);
    }

    public function getInventoryCurrentSnapshotRows(): array
    {
        return InventoryCurrentSnapshotDatabaseQuery::get();
    }

    public function getInventoryAsOfSnapshotRows(string $asOf): array
    {
        return (new InventoryAsOfSnapshotQuery(new InventoryCostingProjectionBuilder))->rows($asOf);
    }

    public function getInventoryStockValueSummary(
        string $fromMutationDate,
        string $toMutationDate,
    ): array {
        return InventoryStockValueSummaryDatabaseQuery::get($fromMutationDate, $toMutationDate);
    }
}
