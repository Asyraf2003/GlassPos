<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class FinalizeSupplierPaymentProofDirectUploadContractFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    private const HANDLER = 'App\\Application\\Procurement\\UseCases\\FinalizeSupplierPaymentProofDirectUploadHandler';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2_private');
    }

    public function test_finalize_rejects_wrong_actor_without_mutating_payment_or_audit(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-actor-1', 'invoice-finalize-actor-1');
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-actor-1',
            'actor-owner-1',
            'supplier_payment',
            'payment-finalize-actor-1',
            null,
            'prepared',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-actor-1'), $content);

        $result = $this->finalize('intent-finalize-actor-1', 'actor-attacker-1');

        self::assertTrue($result->isFailure());
        $this->assertPaymentStillPending('payment-finalize-actor-1');
        self::assertSame(0, $this->attachmentCount('payment-finalize-actor-1'));
        self::assertSame(0, $this->auditCount('supplier_payment_proof_attached'));
        self::assertSame('prepared', $this->intentStatus('intent-finalize-actor-1'));
    }

    public function test_finalize_missing_real_object_causes_zero_business_mutation(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-missing-1', 'invoice-finalize-missing-1');
        $this->seedPreparedIntent(
            'intent-finalize-missing-1',
            'actor-finalize-missing-1',
            'supplier_payment',
            'payment-finalize-missing-1',
            null,
            'prepared',
            1024,
        );

        $result = $this->finalize('intent-finalize-missing-1', 'actor-finalize-missing-1');

        self::assertTrue($result->isFailure());
        $this->assertPaymentStillPending('payment-finalize-missing-1');
        self::assertSame(0, $this->attachmentCount('payment-finalize-missing-1'));
        self::assertSame(0, $this->auditCount('supplier_payment_proof_attached'));
        self::assertFalse(Storage::disk('r2_private')->exists($this->finalPrefix('payment-finalize-missing-1')));
    }

    public function test_finalize_rejects_actual_size_mismatch_before_business_mutation(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-size-1', 'invoice-finalize-size-1');
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-size-1',
            'actor-finalize-size-1',
            'supplier_payment',
            'payment-finalize-size-1',
            null,
            'prepared',
            strlen($content) + 1,
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-size-1'), $content);

        $result = $this->finalize('intent-finalize-size-1', 'actor-finalize-size-1');

        self::assertTrue($result->isFailure());
        $this->assertPaymentStillPending('payment-finalize-size-1');
        self::assertSame(0, $this->attachmentCount('payment-finalize-size-1'));
        self::assertSame(0, $this->auditCount('supplier_payment_proof_attached'));
    }

    public function test_finalize_rejects_client_declared_pdf_when_real_content_is_not_allowed_pdf(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-mime-1', 'invoice-finalize-mime-1');
        $content = 'plain text pretending to be a pdf';
        $this->seedPreparedIntent(
            'intent-finalize-mime-1',
            'actor-finalize-mime-1',
            'supplier_payment',
            'payment-finalize-mime-1',
            null,
            'prepared',
            strlen($content),
            'application/pdf',
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-mime-1'), $content);

        $result = $this->finalize('intent-finalize-mime-1', 'actor-finalize-mime-1');

        self::assertTrue($result->isFailure());
        $this->assertPaymentStillPending('payment-finalize-mime-1');
        self::assertSame(0, $this->attachmentCount('payment-finalize-mime-1'));
        self::assertSame(0, $this->auditCount('supplier_payment_proof_attached'));
    }

    public function test_finalize_does_not_run_second_mutation_when_intent_is_already_finalizing(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-lock-1', 'invoice-finalize-lock-1');
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-lock-1',
            'actor-finalize-lock-1',
            'supplier_payment',
            'payment-finalize-lock-1',
            null,
            'finalizing',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-lock-1'), $content);

        $result = $this->finalize('intent-finalize-lock-1', 'actor-finalize-lock-1');

        self::assertTrue($result->isFailure());
        $this->assertPaymentStillPending('payment-finalize-lock-1');
        self::assertSame(0, $this->attachmentCount('payment-finalize-lock-1'));
        self::assertSame(0, $this->auditCount('supplier_payment_proof_attached'));
        self::assertSame('finalizing', $this->intentStatus('intent-finalize-lock-1'));
    }

    public function test_finalize_existing_payment_promotes_verified_object_and_persists_verified_metadata(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-success-1', 'invoice-finalize-success-1');
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-success-1',
            'actor-finalize-success-1',
            'supplier_payment',
            'payment-finalize-success-1',
            null,
            'prepared',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-success-1'), $content);

        $result = $this->finalize('intent-finalize-success-1', 'actor-finalize-success-1');

        self::assertTrue($result->isSuccess());
        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-finalize-success-1',
            'proof_status' => 'uploaded',
            'proof_storage_path' => null,
        ]);

        $attachment = DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', 'payment-finalize-success-1')
            ->first();

        self::assertNotNull($attachment);
        self::assertSame('application/pdf', (string) $attachment->mime_type);
        self::assertSame(strlen($content), (int) $attachment->file_size_bytes);
        self::assertStringStartsWith(
            'supplier-payment-proofs/payment-finalize-success-1/',
            (string) $attachment->storage_path,
        );
        self::assertStringEndsWith('.pdf', (string) $attachment->storage_path);
        self::assertTrue(Storage::disk('r2_private')->exists((string) $attachment->storage_path));
        self::assertFalse(Storage::disk('r2_private')->exists($this->stagingPath('intent-finalize-success-1')));
        self::assertSame('finalized', $this->intentStatus('intent-finalize-success-1'));
        self::assertSame(1, $this->auditCount('supplier_payment_proof_attached'));

        $intentFile = $this->intentFile('intent-finalize-success-1');
        self::assertSame((string) $attachment->storage_path, (string) $intentFile->final_storage_path);
        self::assertSame('application/pdf', (string) $intentFile->verified_mime_type);
        self::assertSame(strlen($content), (int) $intentFile->verified_size_bytes);
    }

    public function test_finalize_replay_returns_success_without_duplicate_attachment_or_audit(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedPaymentFixture('payment-finalize-replay-1', 'invoice-finalize-replay-1');
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-replay-1',
            'actor-finalize-replay-1',
            'supplier_payment',
            'payment-finalize-replay-1',
            null,
            'prepared',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-replay-1'), $content);

        $first = $this->finalize('intent-finalize-replay-1', 'actor-finalize-replay-1');
        $second = $this->finalize('intent-finalize-replay-1', 'actor-finalize-replay-1');

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isSuccess());
        self::assertSame($first->data(), $second->data());
        self::assertSame(1, $this->attachmentCount('payment-finalize-replay-1'));
        self::assertSame(1, $this->auditCount('supplier_payment_proof_attached'));
        self::assertSame('finalized', $this->intentStatus('intent-finalize-replay-1'));
    }

    public function test_finalize_invoice_uses_reserved_payment_id_and_creates_payment_only_after_verification(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedInvoiceFixture('invoice-finalize-reserved-1', 100000);
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-reserved-1',
            'actor-finalize-reserved-1',
            'supplier_invoice',
            'invoice-finalize-reserved-1',
            'payment-reserved-finalize-1',
            'prepared',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-reserved-1'), $content);

        self::assertSame(0, DB::table('supplier_payments')->where('id', 'payment-reserved-finalize-1')->count());

        $result = $this->finalize('intent-finalize-reserved-1', 'actor-finalize-reserved-1');

        self::assertTrue($result->isSuccess());
        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-reserved-finalize-1',
            'supplier_invoice_id' => 'invoice-finalize-reserved-1',
            'amount_rupiah' => 100000,
            'proof_status' => 'uploaded',
        ]);
        self::assertSame(1, $this->attachmentCount('payment-reserved-finalize-1'));
        self::assertSame(1, $this->auditCount('supplier_invoice_payment_proof_uploaded'));
        self::assertSame('finalized', $this->intentStatus('intent-finalize-reserved-1'));
    }

    public function test_finalize_invoice_revalidates_business_state_changed_during_browser_upload(): void
    {
        $this->assertFinalizeHandlerExists();
        $this->seedInvoiceFixture('invoice-finalize-state-1', 100000);
        $content = $this->pdfBytes();
        $this->seedPreparedIntent(
            'intent-finalize-state-1',
            'actor-finalize-state-1',
            'supplier_invoice',
            'invoice-finalize-state-1',
            'payment-reserved-state-1',
            'prepared',
            strlen($content),
        );
        Storage::disk('r2_private')->put($this->stagingPath('intent-finalize-state-1'), $content);

        $this->seedMinimalSupplierPayment(
            'payment-won-race-1',
            'invoice-finalize-state-1',
            100000,
            '2026-09-03',
            'uploaded',
            null,
        );

        $result = $this->finalize('intent-finalize-state-1', 'actor-finalize-state-1');

        self::assertTrue($result->isFailure());
        self::assertSame(0, DB::table('supplier_payments')->where('id', 'payment-reserved-state-1')->count());
        self::assertSame(0, $this->attachmentCount('payment-reserved-state-1'));
        self::assertSame(0, $this->auditCount('supplier_invoice_payment_proof_uploaded'));
    }

    private function assertFinalizeHandlerExists(): void
    {
        self::assertTrue(class_exists(self::HANDLER), self::HANDLER.' must exist for the direct-upload application contract.');
    }

    private function finalize(string $intentId, string $actorId): Result
    {
        $handler = $this->app->make(self::HANDLER);
        self::assertTrue(method_exists($handler, 'handle'), self::HANDLER.'::handle() must exist.');

        $result = call_user_func([$handler, 'handle'], $intentId, $actorId);
        self::assertInstanceOf(Result::class, $result);

        return $result;
    }

    private function seedPreparedIntent(
        string $intentId,
        string $actorId,
        string $scopeType,
        string $scopeId,
        ?string $reservedPaymentId,
        string $status,
        int $declaredSize,
        string $declaredMime = 'application/pdf',
    ): void {
        DB::table('supplier_payment_proof_upload_intents')->insert([
            'id' => $intentId,
            'actor_id' => $actorId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'reserved_supplier_payment_id' => $reservedPaymentId,
            'idempotency_key' => 'idem-'.$intentId,
            'request_hash' => hash('sha256', $intentId),
            'status' => $status,
            'locked_at' => $status === 'finalizing' ? now() : null,
            'finalized_at' => null,
            'expires_at' => now()->addMinutes(15),
            'result_payload_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('supplier_payment_proof_upload_intent_files')->insert([
            'id' => 'file-'.$intentId,
            'upload_intent_id' => $intentId,
            'ordinal' => 1,
            'staging_path' => $this->stagingPath($intentId),
            'final_storage_path' => null,
            'original_filename' => 'proof-client-name.pdf',
            'declared_mime_type' => $declaredMime,
            'declared_size_bytes' => $declaredSize,
            'verified_mime_type' => null,
            'verified_size_bytes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function intentStatus(string $intentId): string
    {
        return (string) DB::table('supplier_payment_proof_upload_intents')
            ->where('id', $intentId)
            ->value('status');
    }

    private function intentFile(string $intentId): object
    {
        $row = DB::table('supplier_payment_proof_upload_intent_files')
            ->where('upload_intent_id', $intentId)
            ->first();

        self::assertNotNull($row);

        return $row;
    }

    private function attachmentCount(string $paymentId): int
    {
        return (int) DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', $paymentId)
            ->count();
    }

    private function auditCount(string $event): int
    {
        return (int) DB::table('audit_logs')->where('event', $event)->count();
    }

    private function assertPaymentStillPending(string $paymentId): void
    {
        $this->assertDatabaseHas('supplier_payments', [
            'id' => $paymentId,
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);
    }

    private function stagingPath(string $intentId): string
    {
        return 'supplier-payment-proof-uploads/'.$intentId.'/file-1.upload';
    }

    private function finalPrefix(string $paymentId): string
    {
        return 'supplier-payment-proofs/'.$paymentId;
    }

    private function pdfBytes(): string
    {
        return "%PDF-1.4\n% GlassPos direct-upload verification fixture\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n";
    }

    private function seedPaymentFixture(string $paymentId, string $invoiceId): void
    {
        $this->seedInvoiceFixture($invoiceId, 100000);
        $this->seedMinimalSupplierPayment(
            $paymentId,
            $invoiceId,
            30000,
            '2026-09-03',
            'pending',
            null,
        );
    }

    private function seedInvoiceFixture(string $invoiceId, int $grandTotalRupiah): void
    {
        $suffix = str_replace('_', '-', $invoiceId);

        $this->seedMinimalSupplier(
            'supplier-'.$suffix,
            'PT Supplier Finalize Direct Upload',
            'pt supplier finalize direct upload',
        );
        $this->seedMinimalProduct(
            'product-'.$suffix,
            'DU-FIN-001',
            'Produk Finalize Direct Upload',
            'Brand DU',
            100,
            75000,
        );
        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-'.$suffix,
            '2026-09-01',
            '2026-09-30',
            $grandTotalRupiah,
            'PT Supplier Finalize Direct Upload',
        );
        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-'.$suffix,
            $invoiceId,
            'product-'.$suffix,
            2,
            $grandTotalRupiah,
            intdiv($grandTotalRupiah, 2),
            'DU-FIN-001',
            'Produk Finalize Direct Upload',
            'Brand DU',
            100,
        );
    }
}
