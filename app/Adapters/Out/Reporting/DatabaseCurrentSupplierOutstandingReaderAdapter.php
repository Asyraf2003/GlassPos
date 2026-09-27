<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\CurrentSupplierOutstandingReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseCurrentSupplierOutstandingReaderAdapter implements CurrentSupplierOutstandingReaderPort
{
    public function __construct(private readonly SupplierPayableReportingQueryFactory $queries)
    {
    }

    public function totalOutstandingRupiah(): int
    {
        $balance = 'supplier_invoices.grand_total_rupiah - COALESCE(payment_totals.total_paid_rupiah, 0)';

        return (int) $this->queries->invoiceBalances()
            ->whereRaw('('.$balance.') > 0')
            ->sum(DB::raw($balance));
    }
}
