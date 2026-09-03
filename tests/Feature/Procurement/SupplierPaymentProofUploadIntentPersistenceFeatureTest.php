<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\DatabaseSupplierPaymentProofUploadIntentAdapter;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierPaymentProofUploadIntentPersistenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SupplierPaymentProofUploadIntentPort $intents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intents = $this->app->make(SupplierPaymentProofUploadIntentPort::class);
    }

    public function test_service_provider_binds_upload_intent_port_to_database_adapter(): void
    {
        self::assertInstanceOf(DatabaseSupplierPaymentProofUploadIntentAdapter::class, $this->intents);
    }

    public function test_create_and_find_preserves_actor_scope_and_ordered_files(): void
    {
        self::assertTrue($this->createIntent('intent-persist-1', 'actor-a', 'idem-a'));

        $found = $this->intents->findForPrepare(
            'actor-a',
            'supplier_payment',
            'payment-1',
            'idem-a',
        );

        self::assertNotNull($found);
        self::assertSame('intent-persist-1', $found['id']);
        self::assertSame('prepared', $found['status']);
        self::assertCount(2, $found['files']);
        self::assertSame(1, $found['files'][0]['ordinal']);
        self::assertSame(2, $found['files'][1]['ordinal']);
        self::assertNull($found['files'][0]['final_storage_path']);
    }

    public function test_unique_actor_scope_key_prevents_duplicate_intent_and_child_rows(): void
    {
        self::assertTrue($this->createIntent('intent-unique-1', 'actor-a', 'idem-shared'));
        self::assertFalse($this->createIntent('intent-unique-2', 'actor-a', 'idem-shared'));

        self::assertSame(1, DB::table('supplier_payment_proof_upload_intents')->count());
        self::assertSame(2, DB::table('supplier_payment_proof_upload_intent_files')->count());
    }

    public function test_finalize_claim_is_actor_bound_atomic_and_releasable(): void
    {
        self::assertTrue($this->createIntent('intent-claim-1', 'actor-owner', 'idem-claim'));

        self::assertFalse($this->intents->claimForFinalize('intent-claim-1', 'actor-other'));
        self::assertTrue($this->intents->claimForFinalize('intent-claim-1', 'actor-owner'));
        self::assertFalse($this->intents->claimForFinalize('intent-claim-1', 'actor-owner'));

        $claimed = $this->intents->findByIdForActor('intent-claim-1', 'actor-owner');
        self::assertSame('finalizing', $claimed['status'] ?? null);
        self::assertNotNull($claimed['locked_at'] ?? null);

        self::assertTrue($this->intents->releaseFinalizeClaim('intent-claim-1', 'actor-owner'));
        self::assertTrue($this->intents->claimForFinalize('intent-claim-1', 'actor-owner'));
    }

    public function test_expired_intent_cannot_be_claimed_for_finalize(): void
    {
        self::assertTrue($this->createIntent(
            'intent-expired-1',
            'actor-expired',
            'idem-expired',
            new DateTimeImmutable('-1 minute'),
        ));

        self::assertFalse($this->intents->claimForFinalize('intent-expired-1', 'actor-expired'));
        self::assertSame(
            'prepared',
            $this->intents->findByIdForActor('intent-expired-1', 'actor-expired')['status'] ?? null,
        );
    }

    public function test_verified_file_and_finalized_result_are_persisted_once(): void
    {
        self::assertTrue($this->createIntent('intent-final-1', 'actor-final', 'idem-final'));
        self::assertTrue($this->intents->claimForFinalize('intent-final-1', 'actor-final'));

        self::assertTrue($this->intents->recordVerifiedFile(
            'intent-final-1',
            'file-intent-final-1-1',
            'supplier-payment-proofs/payment-1/final.pdf',
            'application/pdf',
            111,
        ));
        self::assertFalse($this->intents->recordVerifiedFile(
            'intent-final-1',
            'file-intent-final-1-1',
            'supplier-payment-proofs/payment-1/replay.pdf',
            'application/pdf',
            111,
        ));

        $payload = [
            'supplier_payment_id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'proof_status' => 'uploaded',
            'attachment_count' => 2,
        ];

        self::assertTrue($this->intents->markFinalized('intent-final-1', 'actor-final', $payload));
        self::assertFalse($this->intents->markFinalized('intent-final-1', 'actor-final', $payload));

        $found = $this->intents->findByIdForActor('intent-final-1', 'actor-final');
        self::assertSame('finalized', $found['status'] ?? null);
        self::assertSame($payload, $found['result_payload'] ?? null);
        self::assertSame(
            'supplier-payment-proofs/payment-1/final.pdf',
            $found['files'][0]['final_storage_path'] ?? null,
        );
        self::assertSame('application/pdf', $found['files'][0]['verified_mime_type'] ?? null);
        self::assertSame(111, $found['files'][0]['verified_size_bytes'] ?? null);
    }

    private function createIntent(
        string $intentId,
        string $actorId,
        string $idempotencyKey,
        ?DateTimeImmutable $expiresAt = null,
    ): bool {
        return $this->intents->createPrepared(
            $intentId,
            $actorId,
            'supplier_payment',
            'payment-1',
            null,
            $idempotencyKey,
            hash('sha256', $intentId),
            $expiresAt ?? new DateTimeImmutable('+15 minutes'),
            [
                $this->file($intentId, 1, 'first.pdf', 111),
                $this->file($intentId, 2, 'second.webp', 222),
            ],
        );
    }

    /** @return array{id:string,ordinal:int,staging_path:string,original_filename:string,declared_mime_type:string,declared_size_bytes:int} */
    private function file(string $intentId, int $ordinal, string $name, int $size): array
    {
        return [
            'id' => sprintf('file-%s-%d', $intentId, $ordinal),
            'ordinal' => $ordinal,
            'staging_path' => sprintf('supplier-payment-proof-uploads/%s/file-%d.upload', $intentId, $ordinal),
            'original_filename' => $name,
            'declared_mime_type' => str_ends_with($name, '.webp') ? 'image/webp' : 'application/pdf',
            'declared_size_bytes' => $size,
        ];
    }
}
