<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\EmployeeDebtReportingSourceReaderPort;

final class DatabaseEmployeeDebtReportingSourceReaderAdapter implements EmployeeDebtReportingSourceReaderPort
{
    public function __construct(private readonly EmployeeDebtTemporalQuery $query) {}

    public function getEmployeeDebtSummaryRows(string $fromRecordedDate, string $toRecordedDate): array
    {
        return $this->query->rows($fromRecordedDate, $toRecordedDate);
    }

    public function getEmployeeDebtSummaryReconciliation(string $fromRecordedDate, string $toRecordedDate): array
    {
        $rows = $this->query->rows($fromRecordedDate, $toRecordedDate);

        return [
            'total_rows' => count($rows),
            'total_debt' => array_sum(array_column($rows, 'total_debt')),
            'total_paid_amount' => array_sum(array_column($rows, 'total_paid_amount')),
            'total_remaining_balance' => array_sum(array_column($rows, 'remaining_balance')),
        ];
    }
}
