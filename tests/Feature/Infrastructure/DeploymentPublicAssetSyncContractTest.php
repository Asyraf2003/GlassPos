<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class DeploymentPublicAssetSyncContractTest extends TestCase
{
    public function test_make_deploy_runs_targeted_public_asset_sync_before_package_build(): void
    {
        $makefile = (string) file_get_contents(base_path('Makefile'));
        $script = (string) file_get_contents(base_path('scripts/sync-deploy-public-assets.sh'));

        self::assertStringContainsString('deploy: deploy-sync-assets', $makefile);
        self::assertStringContainsString('bash scripts/sync-deploy-public-assets.sh', $makefile);
        self::assertStringContainsString('bash scripts/build-cpanel-package.sh', $makefile);

        self::assertStringContainsString('git diff --no-renames --name-only --diff-filter=ACMTUXB', $script);
        self::assertStringContainsString('git diff --no-renames --name-only --diff-filter=D', $script);
        self::assertStringContainsString('php artisan r2:upload-public-assets "${upload_args[@]}"', $script);
        self::assertStringNotContainsString('r2:upload-public-assets --force', $script);
        self::assertStringContainsString('DEPLOY_SYNC_R2', $script);
        self::assertStringContainsString('.last-r2-public-assets-sha', $script);
        self::assertStringContainsString('env_value ASSET_VERSION', $script);
        self::assertStringContainsString('tidak dihapus otomatis dari R2', $script);
    }
}
