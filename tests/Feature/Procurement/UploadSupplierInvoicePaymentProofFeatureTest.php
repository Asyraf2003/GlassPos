<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithSupplierPaymentProofDirectUploads;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class UploadSupplierInvoicePaymentProofFeatureTest extends TestCase
{
    use InteractsWithSupplierPaymentProofDirectUploads;
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_can_upload_invoice_payment_proof_and_auto_lunas_full_outstanding(): void
    {
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedInvoiceFixture('invoice-admin-proof-full-1', 100000);

        $response = $this->uploadSupplierPaymentProofDirectly(
            $this->admin(),
            'supplier_invoice',
            'invoice-admin-proof-full-1',
            [$this->directPdf('proof-admin-full.pdf')],
            'invoice-full-1',
        );

        $response->assertOk()->assertJsonPath('success', true);

        $payment = DB::table('supplier_payments')
            ->where('supplier_invoice_id', 'invoice-admin-proof-full-1')
            ->first();

        self::assertNotNull($payment);
        self::assertSame(100000, (int) $payment->amount_rupiah);
        self::assertSame('uploaded', (string) $payment->proof_status);
        self::assertNull($payment->proof_storage_path);

        $attachments = DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', (string) $payment->id)
            ->get();

        self::assertCount(1, $attachments);

        $storedPath = (string) $attachments->first()->storage_path;
        self::assertNotSame('', $storedPath);
        self::assertTrue(Storage::disk('r2_private')->exists($storedPath));

        $this->assertPaidProjection('invoice-admin-proof-full-1', 100000, 1);
    }

    public function test_admin_can_upload_webp_phone_image_payment_proof_and_auto_lunas(): void
    {
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedInvoiceFixture('invoice-admin-proof-webp-1', 100000);

        $response = $this->uploadSupplierPaymentProofDirectly(
            $this->admin(),
            'supplier_invoice',
            'invoice-admin-proof-webp-1',
            [$this->directWebp('proof-admin-phone.webp')],
            'invoice-webp-1',
        );

        $response->assertOk()->assertJsonPath('success', true);

        $payment = DB::table('supplier_payments')
            ->where('supplier_invoice_id', 'invoice-admin-proof-webp-1')
            ->first();

        self::assertNotNull($payment);
        self::assertSame(100000, (int) $payment->amount_rupiah);
        self::assertSame('uploaded', (string) $payment->proof_status);

        $attachments = DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', (string) $payment->id)
            ->get();

        self::assertCount(1, $attachments);
        self::assertSame('proof-admin-phone.webp', (string) $attachments->first()->original_filename);

        $this->assertPaidProjection('invoice-admin-proof-webp-1', 100000, 1);
    }

    public function test_duplicate_invoice_payment_proof_submit_does_not_create_second_payment(): void
    {
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedInvoiceFixture('invoice-admin-proof-duplicate-1', 100000);
        $admin = $this->admin();

        $first = $this->uploadSupplierPaymentProofDirectly(
            $admin,
            'supplier_invoice',
            'invoice-admin-proof-duplicate-1',
            [$this->directPdf('proof-admin-duplicate-1.pdf')],
            'invoice-duplicate-first',
        );
        $first->assertOk()->assertJsonPath('success', true);

        $second = $this->actingAs($admin)->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => 'supplier_invoice',
                'scope_id' => 'invoice-admin-proof-duplicate-1',
                'idempotency_key' => 'invoice-duplicate-second',
                'files' => [[
                    'original_filename' => 'proof-admin-duplicate-2.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => strlen($this->directPdf()['contents']),
                ]],
            ],
        );

        $second->assertUnprocessable()->assertJsonPath('success', false);

        self::assertSame(
            1,
            DB::table('supplier_payments')
                ->where('supplier_invoice_id', 'invoice-admin-proof-duplicate-1')
                ->count()
        );

        self::assertSame(
            100000,
            (int) DB::table('supplier_payments')
                ->where('supplier_invoice_id', 'invoice-admin-proof-duplicate-1')
                ->sum('amount_rupiah')
        );

        $this->assertPaidProjection('invoice-admin-proof-duplicate-1', 100000, 1);
    }

    public function test_admin_invoice_level_payment_proof_pays_only_remaining_outstanding_after_legacy_partial_payment(): void
    {
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedInvoiceFixture('invoice-admin-proof-partial-1', 100000);

        $this->seedMinimalSupplierPayment(
            'payment-admin-proof-existing-1',
            'invoice-admin-proof-partial-1',
            35000,
            '2026-05-12',
            'uploaded'
        );

        $response = $this->uploadSupplierPaymentProofDirectly(
            $this->admin(),
            'supplier_invoice',
            'invoice-admin-proof-partial-1',
            [$this->directPdf('proof-admin-partial.pdf')],
            'invoice-partial-1',
        );

        $response->assertOk()->assertJsonPath('success', true);

        $newPayment = DB::table('supplier_payments')
            ->where('supplier_invoice_id', 'invoice-admin-proof-partial-1')
            ->where('id', '!=', 'payment-admin-proof-existing-1')
            ->first();

        self::assertNotNull($newPayment);
        self::assertSame(65000, (int) $newPayment->amount_rupiah);
        self::assertSame('uploaded', (string) $newPayment->proof_status);

        $this->assertPaidProjection('invoice-admin-proof-partial-1', 100000, 1);
    }

    public function test_admin_cannot_upload_invoice_payment_proof_for_voided_invoice(): void
    {
        $this->fakeSupplierPaymentProofDirectUploads();
        $this->seedInvoiceFixture('invoice-admin-proof-voided-1', 100000);

        DB::table('supplier_invoices')
            ->where('id', 'invoice-admin-proof-voided-1')
            ->update([
                'voided_at' => '2026-05-13 10:00:00',
                'void_reason' => 'Salah input.',
            ]);

        $proof = $this->directPdf('proof-admin-voided.pdf');
        $response = $this->actingAs($this->admin())->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => 'supplier_invoice',
                'scope_id' => 'invoice-admin-proof-voided-1',
                'idempotency_key' => 'invoice-voided-1',
                'files' => [[
                    'original_filename' => $proof['original_filename'],
                    'mime_type' => $proof['mime_type'],
                    'file_size_bytes' => strlen($proof['contents']),
                ]],
            ],
        );

        $response->assertUnprocessable()->assertJsonPath('success', false);

        self::assertSame(
            0,
            DB::table('supplier_payments')
                ->where('supplier_invoice_id', 'invoice-admin-proof-voided-1')
                ->count()
        );
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Upload Supplier Invoice Payment Proof',
            'email' => 'admin-upload-supplier-invoice-payment-proof@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function seedInvoiceFixture(string $invoiceId, int $grandTotalRupiah): void
    {
        $suffix = str_replace('_', '-', $invoiceId);

        $this->seedMinimalSupplier(
            'supplier-'.$suffix,
            'PT Supplier Admin Proof',
            'pt supplier admin proof'
        );

        $this->seedMinimalProduct(
            'product-'.$suffix,
            'KB-ADMIN-PROOF',
            'Ban Admin Proof',
            'Federal',
            100,
            75000
        );

        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-'.$suffix,
            '2026-05-11',
            '2026-05-21',
            $grandTotalRupiah,
            'PT Supplier Admin Proof'
        );

        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-'.$suffix,
            $invoiceId,
            'product-'.$suffix,
            2,
            $grandTotalRupiah,
            intdiv($grandTotalRupiah, 2),
            'KB-ADMIN-PROOF',
            'Ban Admin Proof',
            'Federal',
            100
        );
    }

    private function assertPaidProjection(string $supplierInvoiceId, int $totalPaidRupiah, int $proofAttachmentCount): void
    {
        $projection = DB::table('supplier_invoice_list_projection')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->first();

        self::assertNotNull($projection);
        self::assertSame($totalPaidRupiah, (int) $projection->total_paid_rupiah);
        self::assertSame(0, (int) $projection->outstanding_rupiah);
        self::assertSame('paid', (string) $projection->payment_status);
        self::assertSame($proofAttachmentCount, (int) $projection->proof_attachment_count);
    }
}
