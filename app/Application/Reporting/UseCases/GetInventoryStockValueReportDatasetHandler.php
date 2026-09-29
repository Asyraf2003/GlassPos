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
    ) {}

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

        $snapshotRows = $this->sourceReader->getInventoryAsOfSnapshotRows($toMutationDate);

        $summary = $this->summary->build($snapshotRows, $movementRows);
        $currentRows = $this->sourceReader->getInventoryCurrentSnapshotRows();
        $summary['total_ledger_qty_diff'] = array_sum(array_column($currentRows, 'ledger_qty_diff'));
        $summary['total_ledger_value_diff_rupiah'] = array_sum(array_column($currentRows, 'ledger_value_diff_rupiah'));

        return Result::success([
            'current_diagnostic_rows' => $currentRows,
            'as_of_date' => $toMutationDate,
            'snapshot_rows' => $snapshotRows,
            'movement_rows' => $movementRows,
            'summary' => $summary,
        ]);
    }

    public function handleSummaryOnly(string $fromMutationDate, string $toMutationDate): Result
    {
        return $this->handle($fromMutationDate, $toMutationDate);
    }
}
