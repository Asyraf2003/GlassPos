<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface CurrentCustomerOutstandingReaderPort
{
    public function outstandingRupiah(): int;
}
