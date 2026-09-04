<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;

final class CpanelMaintenanceRuntimeContractTest extends TestCase
{
    public function test_maintenance_clears_cached_configuration_and_web_opcache_before_bootstrap(): void
    {
        $template = $this->contents('deploy/cpanel/clear.php.template');
        $cacheClear = strpos($template, "glob(\$cacheDirectory.'/*.php')");
        $opcacheReset = strpos($template, 'opcache_reset()');
        $redirect = strpos($template, "'&phase=maintain'");
        $bootstrap = strpos($template, "require \$appRoot.'/vendor/autoload.php'");

        self::assertIsInt($cacheClear);
        self::assertIsInt($opcacheReset);
        self::assertIsInt($redirect);
        self::assertIsInt($bootstrap);
        self::assertLessThan($bootstrap, $cacheClear);
        self::assertLessThan($bootstrap, $opcacheReset);
        self::assertLessThan($bootstrap, $redirect);
        self::assertStringContainsString('Pre-bootstrap cache reset phase has not completed.', $template);
    }

    public function test_maintenance_uses_unique_entry_and_checks_private_r2_in_web_runtime(): void
    {
        $builder = $this->contents('scripts/build-cpanel-package.sh');
        $verifier = $this->contents('scripts/verify-cpanel-package.sh');
        $template = $this->contents('deploy/cpanel/clear.php.template');

        self::assertStringContainsString('clear-${clear_token_hash:0:16}.php', $builder);
        self::assertStringContainsString('^clear-[a-f0-9]{16}\\.php$', $verifier);
        self::assertStringContainsString("Storage::disk('r2_private')", $template);
        self::assertStringContainsString('temporaryUploadUrl', $template);
        self::assertStringContainsString('supplier-payment-proof-uploads/deploy-readiness/', $template);
        self::assertStringContainsString('$exception instanceof SafeMaintenanceException', $template);
        self::assertStringNotContainsString("Storage::disk('public')", $template);
        self::assertStringNotContainsString('supplier-payment-proofs/deploy-readiness/', $template);
    }

    private function contents(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
