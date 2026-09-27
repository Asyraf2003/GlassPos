<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\SupplierPayablePeriodBreakdownBuilder;
use App\Application\Reporting\Services\SupplierPayableReportSummaryBuilder;
use App\Application\Reporting\Services\SupplierPayableSupplierBreakdownBuilder;
use App\Application\Shared\DTO\Result;

final class GetSupplierPayableReportDatasetHandler
{
    public function __construct(
        private readonly GetSupplierPayableSummaryHandler $summaryHandler,
        private readonly SupplierPayableReportSummaryBuilder $summary,
        private readonly SupplierPayablePeriodBreakdownBuilder $periods,
        private readonly SupplierPayableSupplierBreakdownBuilder $suppliers,
    ) {
    }

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

        return Result::success([
            'rows' => $rows,
            'summary' => $this->summary->build($rows),
            'period_rows' => $this->periods->build($rows),
            'supplier_rows' => $this->suppliers->build($rows),
        ]);
    }
}
