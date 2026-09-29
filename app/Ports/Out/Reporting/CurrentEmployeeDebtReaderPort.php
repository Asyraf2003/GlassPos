<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface CurrentEmployeeDebtReaderPort
{
    public function outstandingRupiah(): int;
}
