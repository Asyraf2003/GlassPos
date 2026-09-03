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
}
