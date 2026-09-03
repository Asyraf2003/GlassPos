<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use App\Adapters\Out\Procurement\SupplierPaymentProofFinalPathFactory;
use App\Adapters\Out\Procurement\SupplierPaymentProofMimeTypeDetector;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SupplierPaymentProofMimeTypeDetectorTest extends TestCase
{
    #[DataProvider('isoMediaProvider')]
    public function test_detector_recognizes_allowed_heic_and_heif_brands(string $brand, string $expected): void
    {
        self::assertSame($expected, $this->detect("\x00\x00\x00\x18ftyp{$brand}\x00\x00\x00\x00{$brand}"));
    }

    public function test_detector_rejects_avif_even_when_iso_base_media_signature_is_valid(): void
    {
        self::assertSame(
            'application/octet-stream',
            $this->detect("\x00\x00\x00\x18ftypavif\x00\x00\x00\x00avif"),
        );
    }

    public function test_mime_aliases_and_final_extensions_are_server_owned(): void
    {
        self::assertSame('image/jpeg', SupplierPaymentProofMimeTypes::normalizeAllowed(' IMAGE/JPEG '));
        self::assertSame('image/heic', SupplierPaymentProofMimeTypes::normalizeAllowed('image/heic-sequence'));
        self::assertNull(SupplierPaymentProofMimeTypes::normalizeAllowed('text/plain'));
        self::assertMatchesRegularExpression(
            '#^supplier-payment-proofs/payment-1/[a-f0-9]{64}\.heif$#',
            SupplierPaymentProofFinalPathFactory::make('payment-1', 'file-1', 'image/heif'),
        );
    }

    /** @return array<string,array{string,string}> */
    public static function isoMediaProvider(): array
    {
        return [
            'HEIC still image' => ['heic', 'image/heic'],
            'HEIF media interchange' => ['mif1', 'image/heif'],
        ];
    }

    private function detect(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'glasspos-mime-test-');
        self::assertIsString($path);
        file_put_contents($path, $contents);

        try {
            return SupplierPaymentProofMimeTypeDetector::safe($path);
        } finally {
            unlink($path);
        }
    }
}
