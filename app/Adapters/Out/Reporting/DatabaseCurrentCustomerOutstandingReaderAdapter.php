<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Ports\Out\Reporting\CurrentCustomerOutstandingReaderPort;

final class DatabaseCurrentCustomerOutstandingReaderAdapter implements CurrentCustomerOutstandingReaderPort
{
    public function __construct(private readonly TransactionSummaryReportingQuery $query) {}

    public function outstandingRupiah(): int
    {
        return array_sum(array_column($this->query->rows(null, null, 'current'), 'outstanding_rupiah'));
    }
}
