<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashierDashboardPwaInstallFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_dashboard_exposes_pwa_install_menu(): void
    {
        $this->loginAsKasir();

        $response = $this->get(route('cashier.dashboard'));

        $response->assertOk()
            ->assertSee('Download App PWA')
            ->assertSee('data-pwa-install-button', false)
            ->assertSee('assets/static/js/pages/cashier-dashboard/pwa-install.js', false);
    }

    public function test_manifest_points_to_cashier_fullscreen_app(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

        self::assertSame('HyperPOS Kasir', $manifest['name'] ?? null);
        self::assertSame('/cashier/dashboard', $manifest['start_url'] ?? null);
        self::assertSame('fullscreen', $manifest['display'] ?? null);
        self::assertNotEmpty($manifest['icons'] ?? []);
    }
}
