<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashierDashboardPwaInstallFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_cashier_dashboard_does_not_render_pwa_install_affordance(): void
    {
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?0'])
            ->get(route('cashier.dashboard'));

        $response->assertOk()
            ->assertDontSee('Download App PWA')
            ->assertDontSee('data-pwa-install-button', false)
            ->assertDontSee('assets/static/js/pages/cashier-dashboard/pwa-install.js', false);
    }

    public function test_handset_cashier_dashboard_exposes_capability_gated_pwa_install_affordance(): void
    {
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?1'])
            ->get(route('cashier.dashboard'));

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
