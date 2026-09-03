<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofObjectStorageAdapter;
use App\Adapters\Out\Procurement\SupplierPaymentProofStagingObjectVerifier;
use App\Application\Procurement\UseCases\CleanupSupplierPaymentProofUploadsHandler;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Procurement\SupplierPaymentProofObjectStoragePort;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadCleanupPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class SupplierPaymentProofUploadCleanupFeatureTest extends TestCase
{
    use RefreshDatabase;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('r2_private');
        $this->now = new DateTimeImmutable('2026-09-03 12:00:00 UTC');
        $this->app->instance(ClockPort::class, new FixedSupplierProofCleanupClock($this->now));
    }

    public function test_command_cleans_only_atomically_claimed_expired_and_stale_uploads(): void
    {
        $this->seedIntent('expired-prepared', 'prepared', '-1 minute', null, null);
        $this->seedIntent('stale-finalizing', 'finalizing', '+10 minutes', '-31 minutes', 'stale-final.pdf');
        $this->seedIntent('active-prepared', 'prepared', '+10 minutes', null, null);
        $this->seedIntent('fresh-finalizing', 'finalizing', '+10 minutes', '-5 minutes', null);
        $this->seedIntent('already-finalized', 'finalized', '+10 minutes', null, 'durable-final.pdf', true);

        $this->artisan('supplier-payment-proofs:cleanup-uploads')
            ->expectsOutput('Examined: 2')
            ->expectsOutput('Expired: 2')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();

        foreach (['expired-prepared', 'stale-finalizing'] as $intentId) {
            $this->assertDatabaseHas('supplier_payment_proof_upload_intents', ['id' => $intentId, 'status' => 'expired']);
            self::assertFalse(Storage::disk('r2_private')->exists($this->staging($intentId)));
            self::assertStringContainsString('cleanup_completed', (string) DB::table('supplier_payment_proof_upload_intents')
                ->where('id', $intentId)->value('result_payload_json'));
        }

        foreach (['active-prepared', 'fresh-finalizing', 'already-finalized'] as $intentId) {
            self::assertTrue(Storage::disk('r2_private')->exists($this->staging($intentId)));
        }

        self::assertTrue(Storage::disk('r2_private')->exists('supplier-payment-proofs/payment-already-finalized/durable-final.pdf'));
    }

    public function test_failed_object_cleanup_remains_expired_and_is_retried_safely(): void
    {
        $this->seedIntent('cleanup-retry', 'prepared', '-1 minute', null, null);
        $storage = Mockery::mock(SupplierPaymentProofObjectStoragePort::class);
        $storage->shouldReceive('cleanupIntent')->once()->andReturnFalse();
        $this->app->instance(SupplierPaymentProofObjectStoragePort::class, $storage);

        $this->artisan('supplier-payment-proofs:cleanup-uploads')
            ->expectsOutput('Examined: 1')
            ->expectsOutput('Expired: 0')
            ->expectsOutput('Failed: 1')
            ->assertFailed();

        $this->assertDatabaseHas('supplier_payment_proof_upload_intents', [
            'id' => 'cleanup-retry',
            'status' => 'expired',
            'result_payload_json' => null,
        ]);
        self::assertTrue(Storage::disk('r2_private')->exists($this->staging('cleanup-retry')));

        $realStorage = new LaravelSupplierPaymentProofObjectStorageAdapter(
            new SupplierPaymentProofStagingObjectVerifier,
        );
        $result = (new CleanupSupplierPaymentProofUploadsHandler(
            $this->app->make(SupplierPaymentProofUploadCleanupPort::class),
            $realStorage,
            new FixedSupplierProofCleanupClock($this->now),
        ))->handle();

        self::assertSame(['examined' => 1, 'expired' => 1, 'failed' => 0], $result);

        self::assertFalse(Storage::disk('r2_private')->exists($this->staging('cleanup-retry')));
    }

    private function seedIntent(
        string $id,
        string $status,
        string $expiry,
        ?string $lockedAt,
        ?string $finalFilename,
        bool $finalized = false,
    ): void {
        DB::table('supplier_payment_proof_upload_intents')->insert([
            'id' => $id,
            'actor_id' => 'cleanup-actor',
            'scope_type' => 'supplier_payment',
            'scope_id' => 'payment-'.$id,
            'reserved_supplier_payment_id' => null,
            'idempotency_key' => 'key-'.$id,
            'request_hash' => hash('sha256', $id),
            'status' => $status,
            'locked_at' => $lockedAt === null ? null : $this->now->modify($lockedAt),
            'finalized_at' => $finalized ? $this->now : null,
            'expires_at' => $this->now->modify($expiry),
            'result_payload_json' => $finalized ? json_encode(['proof_status' => 'uploaded']) : null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $finalPath = $finalFilename === null ? null : 'supplier-payment-proofs/payment-'.$id.'/'.$finalFilename;
        DB::table('supplier_payment_proof_upload_intent_files')->insert([
            'id' => 'file-'.$id,
            'upload_intent_id' => $id,
            'ordinal' => 1,
            'staging_path' => $this->staging($id),
            'final_storage_path' => $finalPath,
            'original_filename' => 'proof.pdf',
            'declared_mime_type' => 'application/pdf',
            'declared_size_bytes' => 7,
            'verified_mime_type' => $finalPath === null ? null : 'application/pdf',
            'verified_size_bytes' => $finalPath === null ? null : 7,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        Storage::disk('r2_private')->put($this->staging($id), 'staging');

        if ($finalPath !== null) {
            Storage::disk('r2_private')->put($finalPath, 'durable');
        }
    }

    private function staging(string $intentId): string
    {
        return 'supplier-payment-proof-uploads/'.$intentId.'/file.upload';
    }
}

final class FixedSupplierProofCleanupClock implements ClockPort
{
    public function __construct(private readonly DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
