<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Throwable;

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

    public function test_prepare_many_presigns_exact_staging_path_and_normalizes_browser_headers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));
        $disk = Mockery::mock(FilesystemAdapter::class);
        $path = 'supplier-payment-proof-uploads/intent-1/file-1.upload';
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')->once()->withArgs(
            function (string $actualPath, mixed $expiration, array $options) use ($path): bool {
                self::assertSame($path, $actualPath);
                self::assertSame(['ContentType' => 'application/pdf'], $options);
                self::assertSame(now()->addSeconds(900)->getTimestamp(), $expiration->getTimestamp());

                return true;
            },
        )->andReturn([
            'url' => 'https://private-r2.example.test/presigned-put',
            'headers' => [
                'Host' => ['private-r2.example.test'],
                'Content-Length' => ['2048'],
                'Content-Type' => ['application/pdf'],
                'x-example-number' => [123],
            ],
        ]);

        $result = (new LaravelSupplierPaymentProofDirectUploadAdapter)->prepareMany(
            'intent-1',
            [$this->file($path, 'proof.pdf', 'application/pdf', 2048)],
        );

        self::assertTrue($result->isSuccess());
        $prepared = $result->files();
        self::assertCount(1, $prepared);
        self::assertSame($path, $prepared[0]['storage_path']);
        self::assertSame('https://private-r2.example.test/presigned-put', $prepared[0]['upload_url']);
        self::assertSame([
            'Content-Type' => 'application/pdf',
            'x-example-number' => '123',
        ], $prepared[0]['headers']);
    }

    public function test_prepare_many_clamps_ttl_to_supported_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00 UTC'));
        $disk = Mockery::mock(FilesystemAdapter::class);
        Storage::shouldReceive('disk')->twice()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')->once()->withArgs(
            static fn (string $path, mixed $expiration): bool => $expiration->getTimestamp() === now()->addSeconds(60)->getTimestamp(),
        )->andReturn(['url' => 'https://example.test/min', 'headers' => []]);
        $disk->shouldReceive('temporaryUploadUrl')->once()->withArgs(
            static fn (string $path, mixed $expiration): bool => $expiration->getTimestamp() === now()->addSeconds(3600)->getTimestamp(),
        )->andReturn(['url' => 'https://example.test/max', 'headers' => []]);
        $adapter = new LaravelSupplierPaymentProofDirectUploadAdapter;

        self::assertTrue($adapter->prepareMany(
            'intent-min',
            [$this->file('supplier-payment-proof-uploads/intent-min/file.upload', 'proof.pdf', 'application/pdf', 1)],
            1,
        )->isSuccess());
        self::assertTrue($adapter->prepareMany(
            'intent-max',
            [$this->file('supplier-payment-proof-uploads/intent-max/file.upload', 'proof.pdf', 'application/pdf', 1)],
            99999,
        )->isSuccess());
    }

    public function test_validation_failures_are_explicit_and_observable_before_storage_resolution(): void
    {
        Storage::shouldReceive('disk')->never();
        $reporter = new RecordingSupplierPaymentProofFailureReporter;
        $adapter = new LaravelSupplierPaymentProofDirectUploadAdapter($reporter);

        $cases = [
            SupplierPaymentProofFailureCode::INVALID_INTENT_ID => [' ', [$this->file('supplier-payment-proof-uploads/x/a.upload', 'a.pdf', 'application/pdf', 1)]],
            SupplierPaymentProofFailureCode::INVALID_FILE_COUNT => ['intent-empty', []],
            SupplierPaymentProofFailureCode::INVALID_ORIGINAL_FILENAME => ['intent-name', [$this->file('supplier-payment-proof-uploads/intent-name/a.upload', '', 'application/pdf', 1)]],
            SupplierPaymentProofFailureCode::INVALID_DECLARED_MIME => ['intent-mime', [$this->file('supplier-payment-proof-uploads/intent-mime/a.upload', 'a.txt', 'text/plain', 1)]],
            SupplierPaymentProofFailureCode::INVALID_DECLARED_SIZE => ['intent-size', [$this->file('supplier-payment-proof-uploads/intent-size/a.upload', 'a.pdf', 'application/pdf', 10_485_761)]],
            SupplierPaymentProofFailureCode::INVALID_STAGING_PATH => ['intent-path', [$this->file('supplier-payment-proofs/payment/final.pdf', 'a.pdf', 'application/pdf', 1)]],
        ];

        foreach ($cases as $expected => [$intentId, $files]) {
            $result = $adapter->prepareMany($intentId, $files);
            self::assertFalse($result->isSuccess());
            self::assertSame($expected, $result->failureCode()?->value);
        }

        self::assertCount(count($cases), $reporter->reports);
        self::assertSame('prepare.adapter.validation', $reporter->reports[0]['stage']);
    }

    public function test_foreign_and_traversal_staging_paths_are_rejected_explicitly(): void
    {
        Storage::shouldReceive('disk')->never();
        $reporter = new RecordingSupplierPaymentProofFailureReporter;
        $adapter = new LaravelSupplierPaymentProofDirectUploadAdapter($reporter);

        foreach ([
            'supplier-payment-proof-uploads/intent-other/file.upload',
            'supplier-payment-proof-uploads/intent-owner/../escape.upload',
        ] as $path) {
            $result = $adapter->prepareMany(
                'intent-owner',
                [$this->file($path, 'proof.pdf', 'application/pdf', 100)],
            );
            self::assertSame(SupplierPaymentProofFailureCode::INVALID_STAGING_PATH, $result->failureCode());
        }
    }

    public function test_storage_resolution_failure_has_distinct_code_and_report(): void
    {
        $reporter = new RecordingSupplierPaymentProofFailureReporter;
        Storage::shouldReceive('disk')->once()->with('r2_private')->andThrow(new RuntimeException('disk unavailable'));

        $result = (new LaravelSupplierPaymentProofDirectUploadAdapter($reporter))->prepareMany(
            'intent-storage',
            [$this->file('supplier-payment-proof-uploads/intent-storage/a.upload', 'a.pdf', 'application/pdf', 100)],
        );

        self::assertSame(SupplierPaymentProofFailureCode::STORAGE_RESOLUTION_EXCEPTION, $result->failureCode());
        self::assertSame('prepare.presign', $reporter->reports[0]['stage']);
        self::assertInstanceOf(RuntimeException::class, $reporter->reports[0]['exception']);
    }

    public function test_presign_exception_has_distinct_code_and_report(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $reporter = new RecordingSupplierPaymentProofFailureReporter;
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')->once()->andThrow(new RuntimeException('presign failed'));
        $disk->shouldReceive('getAdapter')->once()->andReturn(new \stdClass);

        $result = (new LaravelSupplierPaymentProofDirectUploadAdapter($reporter))->prepareMany(
            'intent-presign',
            [$this->file('supplier-payment-proof-uploads/intent-presign/a.upload', 'a.pdf', 'application/pdf', 100)],
        );

        self::assertSame(SupplierPaymentProofFailureCode::PRESIGN_EXCEPTION, $result->failureCode());
        self::assertSame(SupplierPaymentProofFailureCode::PRESIGN_EXCEPTION, $reporter->reports[0]['code']);
    }

    public function test_empty_presigned_url_is_explicit_and_observable(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $reporter = new RecordingSupplierPaymentProofFailureReporter;
        Storage::shouldReceive('disk')->once()->with('r2_private')->andReturn($disk);
        $disk->shouldReceive('temporaryUploadUrl')->once()->andReturn(['url' => ' ', 'headers' => []]);
        $disk->shouldReceive('getAdapter')->once()->andReturn(new \stdClass);

        $result = (new LaravelSupplierPaymentProofDirectUploadAdapter($reporter))->prepareMany(
            'intent-empty-url',
            [$this->file('supplier-payment-proof-uploads/intent-empty-url/a.upload', 'a.pdf', 'application/pdf', 100)],
        );

        self::assertSame(SupplierPaymentProofFailureCode::EMPTY_PRESIGNED_URL, $result->failureCode());
        self::assertNull($reporter->reports[0]['exception']);
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

final class RecordingSupplierPaymentProofFailureReporter implements SupplierPaymentProofFailureReporterPort
{
    /** @var list<array{stage:string,code:SupplierPaymentProofFailureCode,exception:?Throwable,context:array<string,mixed>}> */
    public array $reports = [];

    public function report(
        string $stage,
        SupplierPaymentProofFailureCode $failureCode,
        ?Throwable $exception = null,
        array $context = [],
    ): void {
        $this->reports[] = [
            'stage' => $stage,
            'code' => $failureCode,
            'exception' => $exception,
            'context' => $context,
        ];
    }
}
