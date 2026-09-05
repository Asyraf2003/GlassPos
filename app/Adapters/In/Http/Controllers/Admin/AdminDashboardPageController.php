<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin;

use App\Adapters\In\Http\Support\HandsetRequestDetector;
use App\Adapters\In\Http\ViewData\Admin\AdminDashboardFilterDrawerViewData;
use App\Adapters\In\Http\ViewData\Admin\AdminDashboardReportExportShortcuts;
use App\Application\Procurement\UseCases\GetMobileSupplierHubPayloadHandler;
use App\Application\Reporting\UseCases\GetAdminDashboardPagePayloadHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminDashboardPageController extends Controller
{
    public function __invoke(
        Request $request,
        GetAdminDashboardPagePayloadHandler $useCase,
        HandsetRequestDetector $handsets,
        GetMobileSupplierHubPayloadHandler $mobileSupplierHub,
    ): View {
        if ($handsets->isHandset($request)) {
            $tab = (string) $request->query('mobile_tab', '');
            $payload = $mobileSupplierHub->handle();

            return view('admin.dashboard.mobile_supplier_hub', [
                'outstandingInvoices' => $payload['outstanding_invoices'],
                'recentPaymentProofs' => $payload['recent_payment_proofs'],
                'mobileTab' => in_array($tab, ['pay', 'history'], true) ? $tab : '',
            ]);
        }

        $month = $request->query('month');
        $dashboard = $useCase->handle(is_string($month) ? $month : null);
        $stockStatusSegments = $dashboard['analytics']['charts']['stock_status_donut']['segments'] ?? [];
        $dashboardStockStatusSegments = array_map(static function ($segment): array {
            $segment = is_array($segment) ? $segment : [];
            $key = (string) ($segment['key'] ?? '');
            $colorToken = (string) ($segment['color_token'] ?? '');
            $severityClass = match (true) {
                $key === 'safe' || $colorToken === 'success' => 'is-safe',
                $key === 'low' || $colorToken === 'warning' => 'is-warning',
                $key === 'critical' || $colorToken === 'danger' => 'is-critical',
                default => 'is-unconfigured',
            };
            $badgeClass = match ($severityClass) {
                'is-safe' => 'bg-soft-success',
                'is-warning' => 'bg-soft-warning',
                'is-critical' => 'bg-soft-danger',
                default => 'bg-soft-info',
            };

            return [
                'label' => (string) ($segment['label'] ?? '-'),
                'value' => (int) ($segment['value'] ?? 0),
                'severity_class' => $severityClass,
                'badge_class' => $badgeClass,
            ];
        }, is_array($stockStatusSegments) ? $stockStatusSegments : []);

        return view('admin.dashboard.index', [
            'dashboard' => $dashboard,
            'dashboardStockStatusSegments' => $dashboardStockStatusSegments,
            'dashboardExportQuery' => [
                'period_mode' => 'monthly',
                'reference_date' => (string) ($dashboard['period']['date_to'] ?? now()->toDateString()),
            ],
            'dashboardFilterDrawer' => AdminDashboardFilterDrawerViewData::fromDashboard($dashboard),
            'dashboardReportExportShortcuts' => AdminDashboardReportExportShortcuts::all(),
        ]);
    }
}
