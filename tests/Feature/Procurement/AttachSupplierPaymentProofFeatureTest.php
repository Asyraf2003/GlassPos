<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class AttachSupplierPaymentProofFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_can_attach_multiple_proofs_to_pending_supplier_payment(): void
    {
        Storage::fake('r2_private');

        $this->seedPaymentFixture('payment-1', 'invoice-1', 'pending', null);

        $response = $this
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->actingAs($this->user())
            ->post(route('admin.procurement.supplier-payments.proof.store', ['supplierPaymentId' => 'payment-1']), [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-a.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->image('proof-b.jpg'),
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));
        $response->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-1',
            'proof_status' => 'uploaded',
            'proof_storage_path' => null,
        ]);

        $attachments = DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', 'payment-1')
            ->orderBy('uploaded_at')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $attachments);

        foreach ($attachments as $attachment) {
            $this->assertNotEmpty((string) $attachment->storage_path);
            $this->assertTrue(Storage::disk('r2_private')->exists((string) $attachment->storage_path));
        }

        $context = (string) DB::table('audit_logs')
            ->where('event', 'supplier_payment_proof_attached')
            ->value('context');

        $this->assertStringContainsString('payment-1', $context);
        $this->assertStringContainsString('attachment_count', $context);
    }

    public function test_admin_can_append_more_proofs_to_same_supplier_payment_after_first_upload(): void
    {
        Storage::fake('r2_private');

        $this->seedPaymentFixture('payment-1', 'invoice-1', 'uploaded', null);
        $this->seedAttachment(
            'attachment-1',
            'payment-1',
            'supplier-payment-proofs/payment-1/existing-proof.pdf',
            'existing-proof.pdf',
            'application/pdf',
            12345,
            '2026-03-20 10:00:00',
            'actor-1',
        );

        Storage::disk('r2_private')->put('supplier-payment-proofs/payment-1/existing-proof.pdf', 'existing');

        $response = $this
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->actingAs($this->user())
            ->post(route('admin.procurement.supplier-payments.proof.store', ['supplierPaymentId' => 'payment-1']), [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-c.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->image('proof-d.png'),
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));
        $response->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-1',
            'proof_status' => 'uploaded',
            'proof_storage_path' => null,
        ]);

        $attachments = DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', 'payment-1')
            ->orderBy('uploaded_at')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $attachments);

        foreach ($attachments as $attachment) {
            $this->assertNotEmpty((string) $attachment->storage_path);
            $this->assertTrue(Storage::disk('r2_private')->exists((string) $attachment->storage_path));
        }
    }

    private function user(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-proof@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function seedPaymentFixture(
        string $paymentId,
        string $invoiceId,
        string $proofStatus,
        ?string $proofPath
    ): void {
        $this->seedMinimalSupplier('supplier-1', 'PT Supplier Test', 'pt supplier test');
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 75000);
        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-1',
            '2026-03-15',
            '2026-04-15',
            100000,
            'PT Supplier Test'
        );
        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-1',
            $invoiceId,
            'product-1',
            2,
            100000,
            50000,
            'KB-001',
            'Ban Luar',
            'Federal',
            100
        );
        $this->seedMinimalSupplierPayment(
            $paymentId,
            $invoiceId,
            30000,
            '2026-03-20',
            $proofStatus,
            $proofPath
        );
    }

    private function seedAttachment(
        string $id,
        string $supplierPaymentId,
        string $storagePath,
        string $originalFilename,
        string $mimeType,
        int $fileSizeBytes,
        string $uploadedAt,
        string $uploadedByActorId
    ): void {
        DB::table('supplier_payment_proof_attachments')->insert([
            'id' => $id,
            'supplier_payment_id' => $supplierPaymentId,
            'storage_path' => $storagePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size_bytes' => $fileSizeBytes,
            'uploaded_at' => $uploadedAt,
            'uploaded_by_actor_id' => $uploadedByActorId,
        ]);
    }
}
