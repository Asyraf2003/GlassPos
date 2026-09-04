<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class SupplierPaymentProofPrepareWebRuntimeRegressionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_web_runtime_missing_private_disk_is_logged_safely_while_http_response_stays_redacted(): void
    {
        $this->seedInvoice();
        $actor = $this->admin();
        $disks = config('filesystems.disks', []);
        self::assertIsArray($disks);
        unset($disks['r2_private']);
        config(['filesystems.disks' => $disks]);
        Storage::forgetDisk('r2_private');
        $log = Log::spy();

        $response = $this->actingAs($actor)->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => 'supplier_invoice',
                'scope_id' => 'invoice-runtime-regression',
                'idempotency_key' => 'runtime-regression-prepare',
                'files' => [[
                    'original_filename' => 'proof.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 72,
                ]],
            ],
        );

        $response->assertUnprocessable()->assertExactJson([
            'success' => false,
            'data' => null,
            'message' => 'Upload bukti pembayaran gagal disiapkan.',
            'errors' => ['upload_intent' => ['PRESIGN_FAILED']],
        ]);
        self::assertStringNotContainsString('InvalidArgumentException', $response->getContent());
        self::assertStringNotContainsString('r2_private', $response->getContent());

        $intent = DB::table('supplier_payment_proof_upload_intents')
            ->where('scope_type', 'supplier_invoice')
            ->where('scope_id', 'invoice-runtime-regression')
            ->first();
        self::assertNotNull($intent);
        self::assertSame('prepared', (string) $intent->status);
        self::assertSame(1, DB::table('supplier_payment_proof_upload_intent_files')
            ->where('upload_intent_id', (string) $intent->id)
            ->count());
        self::assertSame(0, DB::table('supplier_payments')->count());
        self::assertSame(0, DB::table('supplier_payment_proof_attachments')->count());
        self::assertSame(0, DB::table('audit_logs')->count());

        $log->shouldHaveReceived('error')->once()->with(
            'supplier_payment_proof_direct_upload_failure',
            Mockery::on(static function (array $context) use ($intent): bool {
                return ($context['stage'] ?? null) === 'prepare.presign'
                    && ($context['failure_code'] ?? null) === SupplierPaymentProofFailureCode::STORAGE_RESOLUTION_EXCEPTION->value
                    && ($context['exception_class'] ?? null) === 'InvalidArgumentException'
                    && ($context['runtime']['config_cached'] ?? null) === app()->configurationIsCached()
                    && ($context['context']['upload_intent_id'] ?? null) === (string) $intent->id
                    && ($context['context']['disk'] ?? null) === 'r2_private'
                    && ($context['context']['driver'] ?? null) === ''
                    && ($context['context']['key_configured'] ?? null) === false
                    && ($context['context']['secret_configured'] ?? null) === false
                    && ! str_contains(json_encode($context) ?: '', 'X-Amz-Signature');
            }),
        );
    }

    private function seedInvoice(): void
    {
        $this->seedMinimalSupplier('supplier-runtime-regression', 'PT Runtime Proof', 'pt runtime proof');
        $this->seedMinimalProduct('product-runtime-regression', 'RUNTIME-1', 'Produk Runtime', 'Runtime', 100, 50000);
        $this->seedMinimalSupplierInvoice(
            'invoice-runtime-regression',
            'supplier-runtime-regression',
            '2026-09-04',
            '2026-10-04',
            100000,
        );
        $this->seedMinimalSupplierInvoiceLine(
            'line-runtime-regression',
            'invoice-runtime-regression',
            'product-runtime-regression',
            2,
            100000,
            50000,
        );
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
