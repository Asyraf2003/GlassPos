<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Ports\Out\Reporting\CurrentCustomerOutstandingReaderPort;
use App\Ports\Out\Reporting\CurrentEmployeeDebtReaderPort;
use App\Ports\Out\Reporting\CurrentSupplierOutstandingReaderPort;
use App\Ports\Out\Reporting\DashboardInventoryOverviewReaderPort;
use App\Ports\Out\Reporting\DashboardReportCachePort;

final class GetAdminDashboardOverviewHandler
{
    public function __construct(
        private readonly AdminDashboardOverviewPayloadBuilder $builder,
        private readonly DashboardReportCachePort $cache,
        private readonly CurrentSupplierOutstandingReaderPort $supplierPayable,
        private readonly CurrentEmployeeDebtReaderPort $employeeDebt,
        private readonly CurrentCustomerOutstandingReaderPort $customerOutstanding,
        private readonly DashboardInventoryOverviewReaderPort $inventory,
    ) {}

    public function handle(?string $month = null): array
    {
        $period = AdminDashboardOverviewPeriod::build($month);
        $cacheKey = sprintf(
            'reporting:admin_dashboard_overview:v2:%s:%s:%s:%s',
            $period['active_month'],
            $period['today'],
            $period['from'],
            $period['to'],
        );

        $payload = $this->cache->remember(
            $cacheKey,
            fn (): array => $this->builder->build($period),
        );

        $payload['position']['supplier_outstanding_rupiah'] = $this->supplierPayable->totalOutstandingRupiah();

        $payload['position']['employee_debt_remaining_rupiah'] = $this->employeeDebt->outstandingRupiah();

        $payload['position']['transaction_outstanding_rupiah'] = $this->customerOutstanding->outstandingRupiah();

        $inventory = $this->inventory->getInventorySummary($period['from'], $period['to']);
        foreach (['total_qty_on_hand', 'total_inventory_value_rupiah', 'stock_safe_product_rows', 'stock_low_product_rows', 'stock_critical_product_rows', 'stock_unconfigured_product_rows'] as $key) {
            $payload['stats'][$key] = (int) ($inventory[$key] ?? 0);
        }
        $payload['position']['inventory_value_rupiah'] = $payload['stats']['total_inventory_value_rupiah'];
        $payload['restock_priority_rows'] = $this->inventory->getRestockPriorityRows(5);

        return $payload;
    }
}
