<?php

declare(strict_types=1);

namespace Tests\Unit\Core\ServiceCatalog;

use App\Core\ServiceCatalog\ServiceNameNormalizer;
use PHPUnit\Framework\TestCase;

final class ServiceNameNormalizerTest extends TestCase
{
    public function test_it_matches_parentheses_and_plain_variant(): void
    {
        $normalizer = new ServiceNameNormalizer();

        self::assertSame(
            $normalizer->normalize('Sok Kopling (Besar)'),
            $normalizer->normalize('sok kopling besar'),
        );
    }

    public function test_it_compacts_spacing_and_punctuation(): void
    {
        $normalizer = new ServiceNameNormalizer();

        self::assertSame('setting in kecil', $normalizer->normalize('  Setting--In   (Kecil) '));
    }
}
