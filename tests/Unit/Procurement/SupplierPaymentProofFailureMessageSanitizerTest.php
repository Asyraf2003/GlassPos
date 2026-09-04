<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use App\Adapters\Out\Procurement\SupplierPaymentProofFailureMessageSanitizer;
use PHPUnit\Framework\TestCase;

final class SupplierPaymentProofFailureMessageSanitizerTest extends TestCase
{
    public function test_it_redacts_presigned_urls_and_credential_like_values(): void
    {
        $message = 'PUT https://private.example.test/key?X-Amz-Credential=visible&X-Amz-Signature=visible '
            .'credential=visible secret:visible token => visible access_key=visible raw-actual-secret';
        $sanitized = SupplierPaymentProofFailureMessageSanitizer::sanitize($message, ['raw-actual-secret']);

        self::assertStringNotContainsString('https://', $sanitized);
        self::assertStringNotContainsString('visible', $sanitized);
        self::assertStringContainsString('[redacted-url]', $sanitized);
        self::assertSame(
            'PUT [redacted-url] credential=[redacted] secret=[redacted] token=[redacted] access_key=[redacted] [redacted-secret]',
            $sanitized,
        );
    }
}
