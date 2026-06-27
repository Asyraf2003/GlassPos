<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\InventoryStockValueReportSummaryBuilder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Reporting\InventoryMovementReportingSourceReaderPort;

final class GetInventoryStockValueReportDatasetHandler
{
    public function __construct(
        private readonly GetInventoryMovementSummaryHandler $movementSummaryHandler,
        private readonly InventoryMovementReportingSourceReaderPort $sourceReader,
        private readonly InventoryStockValueReportSummaryBuilder $summary,
    ) {
    }

    public function handle(string $fromMutationDate, string $toMutationDate): Result
    {
        $movementResult = $this->movementSummaryHandler->handle($fromMutationDate, $toMutationDate);

        if ($movementResult->isFailure()) {
            return $movementResult;
        }

        $movementData = $movementResult->data();
        $movementRows = is_array($movementData) && is_array($movementData['rows'] ?? null)
            ? $movementData['rows']
            : [];

        $snapshotRows = $this->sourceReader->getInventoryCurrentSnapshotRows();

        return Result::success([
            'snapshot_rows' => $snapshotRows,
            'movement_rows' => $movementRows,
            'summary' => $this->summary->build($snapshotRows, $movementRows),
        ]);
    }

    public function handleSummaryOnly(string $fromMutationDate, string $toMutationDate): Result
    {
        return Result::success([
            'summary' => $this->sourceReader->getInventoryStockValueSummary(
                $fromMutationDate,
                $toMutationDate,
            ),
        ]);
    }
}
