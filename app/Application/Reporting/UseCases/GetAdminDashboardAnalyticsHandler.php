<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\UseCases\Charts\BuildStockStatusDonutChart;
use App\Ports\Out\Reporting\DashboardInventoryOverviewReaderPort;
use App\Ports\Out\Reporting\DashboardReportCachePort;

final class GetAdminDashboardAnalyticsHandler
{
    public function __construct(
        private readonly AdminDashboardAnalyticsPayloadBuilder $builder,
        private readonly DashboardReportCachePort $cache,
        private readonly DashboardInventoryOverviewReaderPort $inventory,
        private readonly BuildStockStatusDonutChart $stockChart,
    ) {}

    public function handle(?string $month = null): array
    {
        $period = AdminDashboardAnalyticsPeriod::build($month);
        $cacheKey = sprintf(
            'reporting:admin_dashboard_analytics:%s:%s:%s:%s',
            $period['active_month'],
            $period['today'],
            $period['from'],
            $period['to'],
        );

        $payload = $this->cache->remember(
            $cacheKey,
            fn (): array => $this->builder->build($period),
        );
        $payload['charts']['stock_status_donut'] = $this->stockChart->build(
            $this->inventory->getInventorySummary($period['from'], $period['to']), $period['today'],
        );

        return $payload;
    }
}
