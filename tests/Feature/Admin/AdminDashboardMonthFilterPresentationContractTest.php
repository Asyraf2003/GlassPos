<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

final class AdminDashboardMonthFilterPresentationContractTest extends TestCase
{
    public function test_dashboard_month_filter_uses_shared_flatpickr_month_primitive(): void
    {
        $dashboard = (string) file_get_contents(resource_path('views/admin/dashboard/index.blade.php'));
        $drawer = (string) file_get_contents(resource_path('views/admin/dashboard/partials/filter_drawer.blade.php'));
        $assets = (string) file_get_contents(resource_path('views/layouts/partials/date-picker-assets.blade.php'));
        $monthInput = (string) file_get_contents(public_path('assets/static/js/shared/admin-month-input.js'));

        self::assertStringContainsString("@include('layouts.partials.date-picker-assets')", $dashboard);

        self::assertStringContainsString('type="month"', $drawer);
        self::assertStringContainsString('name="month"', $drawer);
        self::assertStringContainsString('data-ui-date="month"', $drawer);
        self::assertStringContainsString('data-ui-date-placeholder="Pilih bulan dashboard"', $drawer);

        self::assertStringContainsString('flatpickr/plugins/monthSelect/style.css', $assets);
        self::assertStringContainsString('flatpickr/plugins/monthSelect/index.js', $assets);
        self::assertStringContainsString('assets/static/js/shared/admin-month-input.js', $assets);

        self::assertStringContainsString('input[data-ui-date="month"]', $monthInput);
        self::assertStringContainsString("dateFormat: 'Y-m'", $monthInput);
        self::assertStringContainsString("altFormat: 'F Y'", $monthInput);
        self::assertStringContainsString('window.monthSelectPlugin', $monthInput);
    }
}
