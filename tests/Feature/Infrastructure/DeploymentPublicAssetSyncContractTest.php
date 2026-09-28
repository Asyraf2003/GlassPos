<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class DeploymentPublicAssetSyncContractTest extends TestCase
{
    public function test_make_deploy_runs_targeted_public_asset_sync_before_package_build(): void
    {
        $makefile = (string) file_get_contents(base_path('Makefile'));
        $syncScript = (string) file_get_contents(base_path('scripts/sync-deploy-public-assets.sh'));
        $packageScript = (string) file_get_contents(base_path('scripts/build-cpanel-package.sh'));

        self::assertStringContainsString('deploy: deploy-sync-assets', $makefile);
        self::assertStringContainsString('bash scripts/sync-deploy-public-assets.sh', $makefile);
        self::assertStringContainsString('bash scripts/build-cpanel-package.sh', $makefile);

        self::assertStringContainsString('git diff --no-renames --name-only --diff-filter=ACMTUXB', $syncScript);
        self::assertStringContainsString('git diff --no-renames --name-only --diff-filter=D', $syncScript);
        self::assertStringContainsString('php artisan r2:upload-public-assets "${upload_args[@]}"', $syncScript);
        self::assertStringNotContainsString('r2:upload-public-assets --force', $syncScript);
        self::assertStringContainsString('DEPLOY_SYNC_R2', $syncScript);
        self::assertStringContainsString('.last-r2-public-assets-sha', $syncScript);
        self::assertStringContainsString('env_value ASSET_VERSION', $syncScript);
        self::assertStringContainsString('tidak dihapus otomatis dari R2', $syncScript);

        self::assertStringContainsString('release_sha="$(git rev-parse HEAD)"', $packageScript);
        self::assertStringContainsString('set_staged_env_value "$app_stage/.env" APP_VERSION "$release_sha"', $packageScript);
        self::assertStringContainsString('set_staged_env_value "$app_stage/.env" ASSET_VERSION "$release_sha"', $packageScript);
        self::assertStringContainsString('cp "$ENV_FILE" "$app_stage/.env"', $packageScript);
        self::assertStringNotContainsString('set_staged_env_value "$ENV_FILE"', $packageScript);
    }
}
