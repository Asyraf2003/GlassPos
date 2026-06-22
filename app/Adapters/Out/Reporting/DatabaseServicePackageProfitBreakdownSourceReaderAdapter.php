<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdownQuery;
use App\Ports\Out\Reporting\ServicePackageProfitBreakdownSourceReaderPort;

final class DatabaseServicePackageProfitBreakdownSourceReaderAdapter implements ServicePackageProfitBreakdownSourceReaderPort
{
    public function __construct(
        private readonly ServicePackageProfitBreakdownQuery $query,
    ) {
    }

    public function getRows(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->query->rows($fromTransactionDate, $toTransactionDate);
    }
}
