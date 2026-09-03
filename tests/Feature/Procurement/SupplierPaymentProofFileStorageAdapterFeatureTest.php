<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SupplierPaymentProofFileStorageAdapterFeatureTest extends TestCase
{
    public function test_store_many_uses_server_detected_mime_instead_of_client_controlled_mime(): void
    {
        Storage::fake('r2_private');

        $sourcePath = tempnam(sys_get_temp_dir(), 'hyperpos-proof-');
        self::assertIsString($sourcePath);

        file_put_contents(
            $sourcePath,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
        );

        try {
            $storedFiles = (new LaravelSupplierPaymentProofFileStorageAdapter())->storeMany('payment-1', [
                [
                    'source_path' => $sourcePath,
                    'original_filename' => 'proof.pdf',
                    'mime_type' => 'text/html',
                    'file_size_bytes' => filesize($sourcePath),
                ],
            ]);
        } finally {
            @unlink($sourcePath);
        }

        self::assertCount(1, $storedFiles);
        self::assertSame('application/pdf', $storedFiles[0]['mime_type']);
        self::assertStringStartsWith('supplier-payment-proofs/payment-1/', $storedFiles[0]['storage_path']);
        self::assertTrue(Storage::disk('r2_private')->exists((string) $storedFiles[0]['storage_path']));
    }

    public function test_store_many_keeps_server_detected_webp_mime_as_safe(): void
    {
        Storage::fake('r2_private');

        $sourcePath = tempnam(sys_get_temp_dir(), 'hyperpos-proof-webp-');
        self::assertIsString($sourcePath);

        file_put_contents(
            $sourcePath,
            base64_decode(
                'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AA/vuUAAA=',
                true,
            ) ?: ''
        );

        try {
            $storedFiles = (new LaravelSupplierPaymentProofFileStorageAdapter())->storeMany('payment-1', [
                [
                    'source_path' => $sourcePath,
                    'original_filename' => 'proof.webp',
                    'mime_type' => 'application/octet-stream',
                    'file_size_bytes' => filesize($sourcePath),
                ],
            ]);
        } finally {
            @unlink($sourcePath);
        }

        self::assertCount(1, $storedFiles);
        self::assertSame('image/webp', $storedFiles[0]['mime_type']);
        self::assertStringStartsWith('supplier-payment-proofs/payment-1/', $storedFiles[0]['storage_path']);
        self::assertTrue(Storage::disk('r2_private')->exists((string) $storedFiles[0]['storage_path']));
    }
}
