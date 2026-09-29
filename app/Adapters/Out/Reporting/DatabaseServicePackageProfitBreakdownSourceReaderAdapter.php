<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\Queries\HistoricalServicePackageProfitBreakdownQuery;
use App\Ports\Out\Reporting\ServicePackageProfitBreakdownSourceReaderPort;

final class DatabaseServicePackageProfitBreakdownSourceReaderAdapter implements ServicePackageProfitBreakdownSourceReaderPort
{
    public function __construct(
        private readonly HistoricalServicePackageProfitBreakdownQuery $query,
    ) {}

    public function getRows(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->query->rows($fromTransactionDate, $toTransactionDate);
    }

    public function getSummary(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->query->summary($fromTransactionDate, $toTransactionDate);
    }
}
