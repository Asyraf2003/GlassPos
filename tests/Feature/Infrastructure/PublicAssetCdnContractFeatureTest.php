<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class PublicAssetCdnContractFeatureTest extends TestCase
{
    public function test_asset_helper_defaults_to_public_r2_cdn(): void
    {
        $appConfig = (string) file_get_contents(config_path('app.php'));
        $envExample = (string) file_get_contents(base_path('.env.example'));

        self::assertStringContainsString(
            "'asset_url' => env('ASSET_URL', env('R2_PUBLIC_URL'))",
            $appConfig,
        );
        self::assertStringContainsString(
            'ASSET_URL=https://media.arbiconbengkel.my.id',
            $envExample,
        );
    }

    public function test_origin_sensitive_pwa_root_files_do_not_use_asset_helper(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

        self::assertStringContainsString(
            "href=\"{{ url('/manifest.webmanifest') }}\"",
            $layout,
        );
        self::assertStringContainsString(
            "data-service-worker-url=\"{{ url('/service-worker.js') }}\"",
            $layout,
        );
        self::assertStringNotContainsString("asset('manifest.webmanifest')", $layout);
        self::assertStringNotContainsString("asset('service-worker.js')", $layout);
    }

    public function test_static_assets_continue_to_use_asset_helper(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

        self::assertStringContainsString("asset('assets/compiled/css/app.css')", $layout);
        self::assertStringContainsString("asset('assets/compiled/js/app.js')", $layout);
        self::assertStringContainsString("asset('assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js')", $layout);
        self::assertStringContainsString("asset('assets/compiled/svg/favicon.svg')", $layout);
    }

    public function test_pwa_icons_and_service_worker_fallback_assets_use_public_cdn(): void
    {
        $manifest = (string) file_get_contents(public_path('manifest.webmanifest'));
        $serviceWorker = (string) file_get_contents(public_path('service-worker.js'));

        self::assertStringContainsString(
            'https://media.arbiconbengkel.my.id/assets/static/pwa/hyperpos-icon-192.png',
            $manifest,
        );
        self::assertStringNotContainsString('"src": "/assets/', $manifest);

        self::assertStringContainsString(
            "const PUBLIC_ASSET_BASE = 'https://media.arbiconbengkel.my.id';",
            $serviceWorker,
        );
        self::assertStringContainsString(
            '`${PUBLIC_ASSET_BASE}/assets/compiled/svg/favicon.svg`',
            $serviceWorker,
        );
        self::assertStringNotContainsString("icon: '/assets/", $serviceWorker);
        self::assertStringNotContainsString("badge: '/assets/", $serviceWorker);
    }

    public function test_error_layout_no_longer_depends_on_local_public_asset_files(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/error.blade.php'));

        self::assertStringNotContainsString("file_exists(public_path('assets/", $layout);
        self::assertStringContainsString("asset('assets/compiled/svg/favicon.svg')", $layout);
        self::assertStringContainsString("asset('assets/compiled/css/app.css')", $layout);
        self::assertStringContainsString("asset('assets/compiled/css/error.css')", $layout);
        self::assertStringContainsString("asset('assets/static/js/initTheme.js')", $layout);
    }

    public function test_push_sender_resolves_application_asset_paths_through_cdn_base(): void
    {
        $adapter = (string) file_get_contents(app_path('Adapters/Out/PushNotification/WebPushNotificationSenderAdapter.php'));

        self::assertStringContainsString('str_starts_with($value, \'/assets/\')', $adapter);
        self::assertStringContainsString("config('app.asset_url', '')", $adapter);
        self::assertStringContainsString('$payloadData[\'icon\'] = $this->resolvePublicAssetUrl', $adapter);
        self::assertStringContainsString('$payloadData[\'badge\'] = $this->resolvePublicAssetUrl', $adapter);
    }
}
