<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class SupplierPaymentProofDirectUploadAdapterFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_service_provider_binds_direct_upload_port_to_r2_adapter(): void
    {
        self::assertInstanceOf(
            LaravelSupplierPaymentProofDirectUploadAdapter::class,
            $this->app->make(SupplierPaymentProofDirectUploadPort::class),
        );
    }

    public function test_prepare_many_presigns_exact_persisted_staging_path_and_normalizes_browser_headers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));
        $disk = Mockery::mock();
        $path = 'supplier-payment-proof-uploads/intent-1/file-1.upload';

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $actualPath, mixed $expiration, array $options) use ($path): bool {
                self::assertSame($path, $actualPath);
                self::assertStringStartsNotWith('supplier-payment-proofs/', $actualPath);
                self::assertSame(['ContentType' => 'application/pdf'], $options);
                self::assertSame(now()->addSeconds(900)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn([
                'url' => 'https://private-r2.example.test/presigned-put',
                'headers' => [
                    'Host' => ['private-r2.example.test'],
                    'Content-Length' => ['2048'],
                    'Content-Type' => ['application/pdf'],
                    'x-example-number' => [123],
                ],
            ]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-1',
            [$this->file($path, 'sensitive supplier invoice.pdf', 'application/pdf', 2048)],
        );

        self::assertCount(1, $prepared);
        self::assertSame($path, $prepared[0]['storage_path']);
        self::assertSame('sensitive supplier invoice.pdf', $prepared[0]['original_filename']);
        self::assertSame('application/pdf', $prepared[0]['mime_type']);
        self::assertSame(2048, $prepared[0]['file_size_bytes']);
        self::assertSame('https://private-r2.example.test/presigned-put', $prepared[0]['upload_url']);
        self::assertSame([
            'Content-Type' => 'application/pdf',
            'x-example-number' => '123',
        ], $prepared[0]['headers']);
    }

    public function test_prepare_many_clamps_ttl_below_minimum_to_sixty_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $path, mixed $expiration): bool {
                self::assertSame(now()->addSeconds(60)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn(['url' => 'https://example.test/min-ttl', 'headers' => []]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-min',
            [$this->file('supplier-payment-proof-uploads/intent-min/file-1.upload', 'proof.webp', 'image/webp', 1)],
            1,
        );

        self::assertCount(1, $prepared);
    }

    public function test_prepare_many_clamps_ttl_above_maximum_to_one_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $path, mixed $expiration): bool {
                self::assertSame(now()->addSeconds(3600)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn(['url' => 'https://example.test/max-ttl', 'headers' => []]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-max',
            [$this->file('supplier-payment-proof-uploads/intent-max/file-1.upload', 'proof.png', 'image/png', 1)],
            99999,
        );

        self::assertCount(1, $prepared);
    }

    public function test_prepare_many_rejects_empty_intent_without_resolving_storage(): void
    {
        Storage::shouldReceive('disk')->never();

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            '   ',
            [$this->file('supplier-payment-proof-uploads/intent-x/file-1.upload', 'proof.pdf', 'application/pdf', 100)],
        );

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_rejects_final_durable_path_before_presigning(): void
    {
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-final-path',
            [$this->file('supplier-payment-proofs/payment-1/final.pdf', 'proof.pdf', 'application/pdf', 100)],
        );

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_rejects_foreign_intent_and_traversal_paths_before_presigning(): void
    {
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->twice()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');
        $adapter = new LaravelSupplierPaymentProofDirectUploadAdapter;

        self::assertSame([], $adapter->prepareMany(
            'intent-owner',
            [$this->file('supplier-payment-proof-uploads/intent-other/file-1.upload', 'proof.pdf', 'application/pdf', 100)],
        ));
        self::assertSame([], $adapter->prepareMany(
            'intent-owner',
            [$this->file('supplier-payment-proof-uploads/intent-owner/../escape.upload', 'proof.pdf', 'application/pdf', 100)],
        ));
    }

    public function test_prepare_many_rejects_incomplete_metadata_before_presigning(): void
    {
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany('intent-invalid', [[
            'storage_path' => 'supplier-payment-proof-uploads/intent-invalid/file-1.upload',
            'original_filename' => 'proof.pdf',
            'mime_type' => '',
            'file_size_bytes' => 0,
        ]]);

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_rejects_non_allowlisted_mime_and_oversized_declaration(): void
    {
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->twice()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');
        $adapter = new LaravelSupplierPaymentProofDirectUploadAdapter;

        self::assertSame([], $adapter->prepareMany(
            'intent-mime',
            [$this->file('supplier-payment-proof-uploads/intent-mime/file.upload', 'proof.txt', 'text/plain', 10)],
        ));
        self::assertSame([], $adapter->prepareMany(
            'intent-size',
            [$this->file('supplier-payment-proof-uploads/intent-size/file.upload', 'proof.pdf', 'application/pdf', 10485761)],
        ));
    }

    public function test_prepare_many_fails_closed_when_presigning_throws(): void
    {
        $disk = Mockery::mock();
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')->once()->andThrow(new RuntimeException('presign failed'));

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-error',
            [$this->file('supplier-payment-proof-uploads/intent-error/file-1.upload', 'proof.pdf', 'application/pdf', 100)],
        );

        self::assertSame([], $prepared);
    }

    /** @return array{storage_path:string,original_filename:string,mime_type:string,file_size_bytes:int} */
    private function file(string $path, string $name, string $mimeType, int $size): array
    {
        return [
            'storage_path' => $path,
            'original_filename' => $name,
            'mime_type' => $mimeType,
            'file_size_bytes' => $size,
        ];
    }
}
