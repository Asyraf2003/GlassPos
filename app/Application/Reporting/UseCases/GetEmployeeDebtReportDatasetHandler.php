<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\EmployeeDebtPeriodBreakdownBuilder;
use App\Application\Reporting\Services\EmployeeDebtReportSummaryBuilder;
use App\Application\Reporting\Services\EmployeeDebtStatusBreakdownBuilder;
use App\Application\Reporting\Services\EmployeeDebtTemporalSummaryRows;
use App\Application\Shared\DTO\Result;

final class GetEmployeeDebtReportDatasetHandler
{
    public function __construct(
        private readonly GetEmployeeDebtSummaryHandler $summaryHandler,
        private readonly EmployeeDebtReportSummaryBuilder $summary,
        private readonly EmployeeDebtPeriodBreakdownBuilder $periods,
        private readonly EmployeeDebtStatusBreakdownBuilder $statuses,
        private readonly EmployeeDebtTemporalSummaryRows $temporalRows,
    ) {}

    public function handle(string $fromRecordedDate, string $toRecordedDate): Result
    {
        $result = $this->summaryHandler->handle($fromRecordedDate, $toRecordedDate);

        if ($result->isFailure()) {
            return $result;
        }

        $data = $result->data();
        $rows = is_array($data) && is_array($data['rows'] ?? null)
            ? $data['rows']
            : [];

        $statusRows = $this->statuses->build($rows);

        $summary = $this->summary->build($rows, $statusRows);

        return Result::success([
            'as_of_date' => $toRecordedDate,
            'temporal_summary_rows' => $this->temporalRows->build($summary, $toRecordedDate),
            'rows' => $rows,
            'summary' => $summary,
            'period_rows' => $this->periods->build($rows),
            'status_rows' => $statusRows,
        ]);
    }
}
