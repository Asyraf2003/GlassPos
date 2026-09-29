<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\CurrentEmployeeDebtReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseCurrentEmployeeDebtReaderAdapter implements CurrentEmployeeDebtReaderPort
{
    public function outstandingRupiah(): int
    {
        return (int) DB::table('employee_debts')->where('remaining_balance', '>', 0)->sum('remaining_balance');
    }
}
