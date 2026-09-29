<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\SupplierPayablePeriodBreakdownBuilder;
use App\Application\Reporting\Services\SupplierPayableReportSummaryBuilder;
use App\Application\Reporting\Services\SupplierPayableSupplierBreakdownBuilder;
use App\Application\Reporting\Services\SupplierPayableTemporalSummaryRows;
use App\Application\Shared\DTO\Result;

final class GetSupplierPayableReportDatasetHandler
{
    public function __construct(
        private readonly GetSupplierPayableSummaryHandler $summaryHandler,
        private readonly SupplierPayableReportSummaryBuilder $summary,
        private readonly SupplierPayablePeriodBreakdownBuilder $periods,
        private readonly SupplierPayableSupplierBreakdownBuilder $suppliers,
    ) {}

    public function handle(
        ?string $fromShipmentDate,
        ?string $toShipmentDate,
        string $referenceDate,
    ): Result {
        $result = $this->summaryHandler->handle(
            $fromShipmentDate,
            $toShipmentDate,
            $referenceDate,
        );

        if ($result->isFailure()) {
            return $result;
        }

        $data = $result->data();
        $rows = is_array($data) && is_array($data['rows'] ?? null)
            ? $data['rows']
            : [];

        $summary = $this->summary->build($rows);

        return Result::success([
            'as_of_date' => $toShipmentDate,
            'version_mode' => $toShipmentDate === null ? 'current' : 'as_of',
            'temporal_summary_rows' => SupplierPayableTemporalSummaryRows::build($summary, $fromShipmentDate, $toShipmentDate),
            'rows' => $rows,
            'summary' => $summary,
            'period_rows' => $this->periods->build($rows),
            'supplier_rows' => $this->suppliers->build($rows),
        ]);
    }
}
