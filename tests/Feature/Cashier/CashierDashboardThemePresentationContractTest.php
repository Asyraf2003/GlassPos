<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use Tests\TestCase;

final class CashierDashboardThemePresentationContractTest extends TestCase
{
    public function test_dashboard_marks_device_and_uses_external_presentation_asset(): void
    {
        $view = $this->viewSource('cashier/dashboard/index.blade.php');

        self::assertStringContainsString('data-cashier-dashboard-device=', $view);
        self::assertStringContainsString('cashier-dashboard.css', $view);
        self::assertStringNotContainsString('<style>', $view);
    }

    public function test_desktop_actions_are_four_across_while_handset_keeps_compact_grid(): void
    {
        $css = $this->publicAsset('assets/static/css/cashier-dashboard.css');

        self::assertStringContainsString('[data-cashier-dashboard-device="desktop"] .cashier-home-grid', $css);
        self::assertStringContainsString('grid-template-columns: repeat(4, minmax(0, 1fr));', $css);
        self::assertStringContainsString('[data-cashier-dashboard-device="handset"] .cashier-home-grid', $css);
        self::assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        self::assertStringNotContainsString('max-width: 720px;', $css);
    }

    public function test_hover_uses_color_or_surface_change_without_underlining_text(): void
    {
        $foundation = $this->publicAsset('assets/static/css/ui-foundation.css');
        $dashboard = $this->publicAsset('assets/static/css/cashier-dashboard.css');

        self::assertStringContainsString('text-decoration: none;', $foundation);
        self::assertStringNotContainsString('text-decoration: underline;', $foundation);
        self::assertStringContainsString('.cashier-home-card:hover', $dashboard);
        self::assertStringContainsString('background:', $dashboard);
    }

    public function test_neutral_border_badges_have_theme_aware_root_tokens(): void
    {
        $foundation = $this->publicAsset('assets/static/css/ui-foundation.css');

        self::assertStringContainsString('--ui-badge-neutral-bg:', $foundation);
        self::assertStringContainsString('--ui-badge-neutral-color:', $foundation);
        self::assertStringContainsString('--ui-badge-neutral-border:', $foundation);
        self::assertStringContainsString('html[data-bs-theme="dark"]', $foundation);
        self::assertStringContainsString('.badge.border:not([class*="bg-"])', $foundation);
        self::assertStringContainsString('color: var(--ui-badge-neutral-color);', $foundation);
        self::assertStringContainsString('background-color: var(--ui-badge-neutral-bg);', $foundation);
    }

    private function viewSource(string $path): string
    {
        return (string) file_get_contents(resource_path('views/'.$path));
    }

    private function publicAsset(string $path): string
    {
        return (string) file_get_contents(public_path($path));
    }
}
