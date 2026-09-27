<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface CurrentSupplierOutstandingReaderPort
{
    /** Current positive balances of all non-void invoices, using only active payments. */
    public function totalOutstandingRupiah(): int;
}
