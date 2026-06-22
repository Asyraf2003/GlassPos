<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServicePackageProfitBreakdownReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_service_package_profit_breakdown_page(): void
    {
        $this->get(route('admin.reports.service_package_profit_breakdown.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_service_package_profit_breakdown_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.service_package_profit_breakdown.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_service_package_profit_breakdown_page_from_sidebar(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.service_package_profit_breakdown.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Laba Paket Service');
        $response->assertSee('service-package-profit-breakdown-filter-form', false);
        $response->assertSee('Tanggal transaksi nota');
        $response->assertSee('Jumlah Paket');
        $response->assertSee('Gross Profit Paket');
        $response->assertSee('Belum ada paket service pada periode ini.');
        $response->assertSee(route('admin.reports.service_package_profit_breakdown.index'), false);
    }
}
