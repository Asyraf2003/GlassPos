<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;

final class PrivateR2CorsPolicyContractTest extends TestCase
{
    private const ORIGINS = [
        'https://arbiconbengkel.my.id',
        'http://127.0.0.1:8000',
        'http://localhost:8000',
    ];

    public function test_wrangler_policy_is_explicit_put_only_and_content_type_only(): void
    {
        $policy = $this->json('deploy/cloudflare/glasspos-private-cors.json');

        self::assertSame(self::ORIGINS, $policy['rules'][0]['allowed']['origins'] ?? null);
        self::assertSame(['PUT'], $policy['rules'][0]['allowed']['methods'] ?? null);
        self::assertSame(['Content-Type'], $policy['rules'][0]['allowed']['headers'] ?? null);
        self::assertSame(['ETag'], $policy['rules'][0]['exposeHeaders'] ?? null);
        self::assertSame(900, $policy['rules'][0]['maxAgeSeconds'] ?? null);
        self::assertNotContains('*', self::ORIGINS);
    }

    public function test_dashboard_policy_matches_the_same_private_boundary(): void
    {
        $policy = $this->json('deploy/cloudflare/glasspos-private-cors-dashboard.json');

        self::assertSame(self::ORIGINS, $policy[0]['AllowedOrigins'] ?? null);
        self::assertSame(['PUT'], $policy[0]['AllowedMethods'] ?? null);
        self::assertSame(['Content-Type'], $policy[0]['AllowedHeaders'] ?? null);
        self::assertSame(['ETag'], $policy[0]['ExposeHeaders'] ?? null);
        self::assertSame(900, $policy[0]['MaxAgeSeconds'] ?? null);
    }

    /** @return array<mixed> */
    private function json(string $relativePath): array
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$relativePath);
        self::assertIsString($contents);
        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
