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
        $resolved = $this->app->make(SupplierPaymentProofDirectUploadPort::class);

        self::assertInstanceOf(LaravelSupplierPaymentProofDirectUploadAdapter::class, $resolved);
    }

    public function test_prepare_many_builds_private_presigned_upload_contract_without_exposing_original_filename_in_path(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));

        $disk = Mockery::mock();

        Storage::shouldReceive('disk')
            ->once()
            ->with('r2_private')
            ->andReturn($disk);

        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $path, mixed $expiration, array $options): bool {
                self::assertStringStartsWith('supplier-payment-proofs/payment-1/', $path);
                self::assertStringEndsWith('.pdf', $path);
                self::assertStringNotContainsString('sensitive supplier invoice', $path);
                self::assertSame(['ContentType' => 'application/pdf'], $options);
                self::assertSame(now()->addSeconds(900)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn([
                'url' => 'https://private-r2.example.test/presigned-put',
                'headers' => [
                    'Content-Type' => 'application/pdf',
                    'x-example-number' => 123,
                ],
            ]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('payment-1', [
            [
                'original_filename' => 'sensitive supplier invoice.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 2048,
            ],
        ]);

        self::assertCount(1, $prepared);
        self::assertSame('sensitive supplier invoice.pdf', $prepared[0]['original_filename']);
        self::assertSame('application/pdf', $prepared[0]['mime_type']);
        self::assertSame(2048, $prepared[0]['file_size_bytes']);
        self::assertSame('https://private-r2.example.test/presigned-put', $prepared[0]['upload_url']);
        self::assertSame([
            'Content-Type' => 'application/pdf',
            'x-example-number' => '123',
        ], $prepared[0]['headers']);
        self::assertStringStartsWith('supplier-payment-proofs/payment-1/', $prepared[0]['storage_path']);
        self::assertStringEndsWith('.pdf', $prepared[0]['storage_path']);
    }

    public function test_prepare_many_clamps_ttl_below_minimum_to_sixty_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));

        $disk = Mockery::mock();

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);

        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $path, mixed $expiration, array $options): bool {
                self::assertSame(now()->addSeconds(60)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn(['url' => 'https://example.test/min-ttl', 'headers' => []]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('payment-min', [
            [
                'original_filename' => 'proof.webp',
                'mime_type' => 'image/webp',
                'file_size_bytes' => 1,
            ],
        ], 1);

        self::assertCount(1, $prepared);
    }

    public function test_prepare_many_clamps_ttl_above_maximum_to_one_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));

        $disk = Mockery::mock();

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);

        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->withArgs(function (string $path, mixed $expiration, array $options): bool {
                self::assertSame(now()->addSeconds(3600)->getTimestamp(), $expiration->getTimestamp());

                return true;
            })
            ->andReturn(['url' => 'https://example.test/max-ttl', 'headers' => []]);

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('payment-max', [
            [
                'original_filename' => 'proof.png',
                'mime_type' => 'image/png',
                'file_size_bytes' => 1,
            ],
        ], 99999);

        self::assertCount(1, $prepared);
    }

    public function test_prepare_many_rejects_empty_payment_id_without_resolving_storage(): void
    {
        Storage::shouldReceive('disk')->never();

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('   ', [
            [
                'original_filename' => 'proof.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 100,
            ],
        ]);

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_rejects_path_traversal_payment_id_before_presigning(): void
    {
        $disk = Mockery::mock();

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('../../escape', [
            [
                'original_filename' => 'proof.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 100,
            ],
        ]);

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_rejects_incomplete_file_metadata_before_presigning(): void
    {
        $disk = Mockery::mock();

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldNotReceive('temporaryUploadUrl');

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('payment-invalid', [
            [
                'original_filename' => 'proof.pdf',
                'mime_type' => '',
                'file_size_bytes' => 0,
            ],
        ]);

        self::assertSame([], $prepared);
    }

    public function test_prepare_many_fails_closed_when_presigning_throws(): void
    {
        $disk = Mockery::mock();

        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);

        $disk->shouldReceive('temporaryUploadUrl')
            ->once()
            ->andThrow(new RuntimeException('presign failed'));

        $prepared = (new LaravelSupplierPaymentProofDirectUploadAdapter())->prepareMany('payment-error', [
            [
                'original_filename' => 'proof.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 100,
            ],
        ]);

        self::assertSame([], $prepared);
    }
}
