<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardOperationalPerformance;

use App\Adapters\Out\Reporting\EmployeeDebtDisbursementQuery;

final class EmployeeDebtCashOutPerDayQuery
{
    /**
     * @return list<array{
     *   period_key:string,
     *   period_label:string,
     *   amount_rupiah:int
     * }>
     */
    public function rows(string $fromDate, string $toDate): array
    {
        return (new EmployeeDebtDisbursementQuery)->daily($fromDate, $toDate);
    }
}
