<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\EmployeeDebtReportingReconciliationService;
use App\Application\Reporting\Services\EmployeeDebtSummaryBuilder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Reporting\EmployeeDebtReportingSourceReaderPort;

final class GetEmployeeDebtSummaryHandler
{
    public function __construct(
        private readonly EmployeeDebtReportingSourceReaderPort $sourceReader,
        private readonly EmployeeDebtSummaryBuilder $builder,
        private readonly EmployeeDebtReportingReconciliationService $reconciliation,
    ) {}

    public function handle(string $fromRecordedDate, string $toRecordedDate): Result
    {
        $rawRows = $this->sourceReader->getEmployeeDebtSummaryRows(
            $fromRecordedDate,
            $toRecordedDate,
        );

        $rows = $this->builder->build($rawRows);

        $expected = $this->sourceReader->getEmployeeDebtSummaryReconciliation(
            $fromRecordedDate,
            $toRecordedDate,
        );

        $this->reconciliation->assertEmployeeDebtSummaryMatches($rows, $expected);

        return Result::success([
            'rows' => array_map(
                static fn ($row, array $raw): array => array_merge($raw, $row->toArray()),
                $rows,
                $rawRows,
            ),
        ]);
    }
}
