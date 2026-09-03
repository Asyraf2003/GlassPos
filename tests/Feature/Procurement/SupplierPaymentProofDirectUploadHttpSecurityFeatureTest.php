<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofObjectStorageAdapter;
use App\Adapters\Out\Procurement\SupplierPaymentProofStagingObjectVerifier;
use App\Ports\Out\Procurement\SupplierPaymentProofObjectStoragePort;
use App\Ports\Out\TransactionManagerPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
use RuntimeException;
use Tests\Support\InteractsWithSupplierPaymentProofDirectUploads;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class SupplierPaymentProofDirectUploadHttpSecurityFeatureTest extends TestCase
{
    use InteractsWithSupplierPaymentProofDirectUploads;
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedPaymentFixture();
    }

    public function test_prepare_exposes_only_actor_bound_staging_put_metadata_before_mutation(): void
    {
        $prepared = $this->prepare($this->admin('prepare'), $this->directPdf(), 'http-prepare');

        $prepared->assertOk()->assertJsonPath('success', true);
        self::assertStringStartsWith(
            'supplier-payment-proof-uploads/',
            (string) $prepared->json('data.files.0.storage_path'),
        );
        self::assertStringContainsString('private-r2.example.test', (string) $prepared->json('data.files.0.upload_url'));
        self::assertStringNotContainsString('supplier-payment-proofs/', $prepared->getContent());
        $this->assertDatabaseHas('supplier_payments', ['id' => 'payment-http', 'proof_status' => 'pending']);
        self::assertSame(0, DB::table('supplier_payment_proof_attachments')->count());
        self::assertSame(0, DB::table('audit_logs')->where('event', 'supplier_payment_proof_attached')->count());
    }

    public function test_finalize_hides_foreign_intent_and_performs_zero_mutation(): void
    {
        $owner = $this->admin('owner');
        $prepared = $this->prepare($owner, $this->directPdf(), 'http-foreign');
        $this->stage($prepared, $this->directPdf()['contents']);

        $response = $this->actingAs($this->admin('attacker'))->postJson(route(
            'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
            ['uploadIntentId' => (string) $prepared->json('data.upload_intent_id')],
        ));

        $response->assertNotFound()->assertJsonPath('errors.upload_intent.0', 'UPLOAD_INTENT_NOT_FOUND');
        $this->assertDatabaseHas('supplier_payments', ['id' => 'payment-http', 'proof_status' => 'pending']);
        self::assertSame(0, DB::table('supplier_payment_proof_attachments')->count());
    }

    public function test_finalize_uses_verified_content_mime_and_opaque_extension_not_client_claims(): void
    {
        $actor = $this->admin('mime');
        $webp = $this->directWebp('misleading-client-name.pdf');
        $webp['mime_type'] = 'image/jpeg';

        $response = $this->uploadSupplierPaymentProofDirectly(
            $actor,
            'supplier_payment',
            'payment-http',
            [$webp],
            'http-mime-truth',
        );

        $response->assertOk()->assertJsonPath('success', true);
        $attachment = DB::table('supplier_payment_proof_attachments')->where('supplier_payment_id', 'payment-http')->first();
        self::assertNotNull($attachment);
        self::assertSame('image/webp', (string) $attachment->mime_type);
        self::assertMatchesRegularExpression(
            '#^supplier-payment-proofs/payment-http/[a-f0-9]{64}\.webp$#',
            (string) $attachment->storage_path,
        );
        self::assertStringNotContainsString('misleading-client-name', (string) $attachment->storage_path);
    }

    public function test_finalize_redacts_internal_storage_exception_and_releases_claim(): void
    {
        $actor = $this->admin('redaction');
        $proof = $this->directPdf();
        $prepared = $this->prepare($actor, $proof, 'http-redaction');
        $this->stage($prepared, $proof['contents']);
        $storage = Mockery::mock(SupplierPaymentProofObjectStoragePort::class);
        $storage->shouldReceive('verifyStaging')->once()
            ->andThrow(new RuntimeException('secret-internal-r2-credential'));
        $storage->shouldReceive('deleteMany')->once()->with([])->andReturnTrue();
        $this->app->instance(SupplierPaymentProofObjectStoragePort::class, $storage);

        $intentId = (string) $prepared->json('data.upload_intent_id');
        $response = $this->actingAs($actor)->postJson(route(
            'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
            ['uploadIntentId' => $intentId],
        ));

        $response->assertStatus(500)->assertExactJson([
            'success' => false,
            'data' => null,
            'message' => 'Upload bukti pembayaran gagal difinalisasi.',
            'errors' => ['upload_intent' => ['FINALIZE_FAILED']],
        ]);
        self::assertStringNotContainsString('secret-internal-r2-credential', $response->getContent());
        $this->assertDatabaseHas('supplier_payment_proof_upload_intents', ['id' => $intentId, 'status' => 'prepared']);
    }

    public function test_partial_promotion_failure_deletes_promoted_objects_and_verified_metadata(): void
    {
        $actor = $this->admin('promotion-failure');
        $proof = $this->directPdf();
        $prepared = $this->actingAs($actor)->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => 'supplier_payment',
                'scope_id' => 'payment-http',
                'idempotency_key' => 'http-promotion-failure',
                'files' => array_fill(0, 2, [
                    'original_filename' => 'proof.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => strlen($proof['contents']),
                ]),
            ],
        );
        $prepared->assertOk();
        $finalPath = 'supplier-payment-proofs/payment-http/opaque-first.pdf';
        $storage = Mockery::mock(SupplierPaymentProofObjectStoragePort::class);
        $storage->shouldReceive('verifyStaging')->twice()->andReturnUsing(
            static fn (string $intentId, array $file): array => array_merge($file, [
                'verified_mime_type' => 'application/pdf',
                'verified_size_bytes' => strlen($proof['contents']),
            ]),
        );
        $storage->shouldReceive('promote')->twice()->andReturn([
            'storage_path' => $finalPath,
            'original_filename' => 'proof.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($proof['contents']),
            'intent_file_id' => 'unused',
        ], null);
        $storage->shouldReceive('deleteMany')->once()->with([$finalPath])->andReturnTrue();
        $this->app->instance(SupplierPaymentProofObjectStoragePort::class, $storage);

        $intentId = (string) $prepared->json('data.upload_intent_id');
        $response = $this->actingAs($actor)->postJson(route(
            'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
            ['uploadIntentId' => $intentId],
        ));

        $response->assertUnprocessable()->assertJsonPath('errors.upload_intent.0', 'PROMOTION_FAILED');
        $this->assertDatabaseHas('supplier_payment_proof_upload_intents', ['id' => $intentId, 'status' => 'prepared']);
        self::assertSame(0, DB::table('supplier_payment_proof_upload_intent_files')
            ->where('upload_intent_id', $intentId)->whereNotNull('final_storage_path')->count());
        self::assertSame(0, DB::table('supplier_payment_proof_attachments')->count());
    }

    public function test_object_verification_and_promotion_finish_before_business_transaction_begins(): void
    {
        $actor = $this->admin('boundary');
        $proof = $this->directPdf();
        $prepared = $this->prepare($actor, $proof, 'http-boundary');
        $this->stage($prepared, $proof['contents']);
        $transactions = new RecordingSupplierProofTransactionManager;
        $storage = new TransactionBoundaryObjectStorage(
            new LaravelSupplierPaymentProofObjectStorageAdapter(new SupplierPaymentProofStagingObjectVerifier),
            $transactions,
        );
        $this->app->instance(TransactionManagerPort::class, $transactions);
        $this->app->instance(SupplierPaymentProofObjectStoragePort::class, $storage);

        $response = $this->actingAs($actor)->postJson(route(
            'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
            ['uploadIntentId' => (string) $prepared->json('data.upload_intent_id')],
        ));

        $response->assertOk()->assertJsonPath('success', true);
        self::assertFalse($storage->observedOpenTransaction);
        self::assertSame(1, $transactions->beginCount);
        self::assertSame(1, $transactions->commitCount);
    }

    /** @param array{original_filename:string,mime_type:string,contents:string} $proof */
    private function prepare(User $actor, array $proof, string $key): TestResponse
    {
        return $this->actingAs($actor)->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => 'supplier_payment',
                'scope_id' => 'payment-http',
                'idempotency_key' => $key,
                'files' => [[
                    'original_filename' => $proof['original_filename'],
                    'mime_type' => $proof['mime_type'],
                    'file_size_bytes' => strlen($proof['contents']),
                ]],
            ],
        );
    }

    private function stage(TestResponse $prepared, string $contents): void
    {
        $prepared->assertOk()->assertJsonPath('success', true);
        Storage::disk('r2_private')->put((string) $prepared->json('data.files.0.storage_path'), $contents);
    }

    private function admin(string $suffix): User
    {
        $user = User::query()->create([
            'name' => 'Admin Direct Upload HTTP',
            'email' => 'admin-direct-upload-'.$suffix.'@example.test',
            'password' => 'password123',
        ]);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);

        return $user;
    }

    private function seedPaymentFixture(): void
    {
        $this->seedMinimalSupplier('supplier-http', 'PT HTTP Proof', 'pt http proof');
        $this->seedMinimalProduct('product-http', 'HTTP-1', 'Produk HTTP', 'HTTP', 100, 75000);
        $this->seedMinimalSupplierInvoice('invoice-http', 'supplier-http', '2026-09-01', '2026-09-30', 100000);
        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-http', 'invoice-http', 'product-http', 2, 100000, 50000, 'HTTP-1', 'Produk HTTP', 'HTTP', 100,
        );
        $this->seedMinimalSupplierPayment('payment-http', 'invoice-http', 30000, '2026-09-03', 'pending', null);
    }
}

final class RecordingSupplierProofTransactionManager implements TransactionManagerPort
{
    public bool $active = false;

    public int $beginCount = 0;

    public int $commitCount = 0;

    public function begin(): void
    {
        $this->active = true;
        $this->beginCount++;
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
        $this->commitCount++;
        $this->active = false;
    }

    public function rollBack(): void
    {
        DB::rollBack();
        $this->active = false;
    }
}

final class TransactionBoundaryObjectStorage implements SupplierPaymentProofObjectStoragePort
{
    public bool $observedOpenTransaction = false;

    public function __construct(
        private readonly SupplierPaymentProofObjectStoragePort $storage,
        private readonly RecordingSupplierProofTransactionManager $transactions,
    ) {}

    public function verifyStaging(string $uploadIntentId, array $intentFile): ?array
    {
        $this->observe();

        return $this->storage->verifyStaging($uploadIntentId, $intentFile);
    }

    public function promote(string $uploadIntentId, string $supplierPaymentId, array $verifiedFile): ?array
    {
        $this->observe();

        return $this->storage->promote($uploadIntentId, $supplierPaymentId, $verifiedFile);
    }

    public function deleteMany(array $paths): bool
    {
        return $this->storage->deleteMany($paths);
    }

    public function cleanupIntent(array $intent): bool
    {
        return $this->storage->cleanupIntent($intent);
    }

    private function observe(): void
    {
        $this->observedOpenTransaction = $this->observedOpenTransaction || $this->transactions->active;
    }
}
