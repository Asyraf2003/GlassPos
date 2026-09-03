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

final class UploadSupplierInvoicePaymentProofFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_can_upload_invoice_payment_proof_and_auto_lunas_full_outstanding(): void
    {
        Storage::fake('r2_private');
        $this->seedInvoiceFixture('invoice-admin-proof-full-1', 100000);

        $backUrl = route('admin.procurement.supplier-invoices.payment-proofs.show', [
            'supplierInvoiceId' => 'invoice-admin-proof-full-1',
        ]);

        $response = $this->actingAs($this->admin())
            ->from($backUrl)
            ->post(route('admin.procurement.supplier-invoices.payment-proof.store', [
                'supplierInvoiceId' => 'invoice-admin-proof-full-1',
            ]), [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-full.pdf', 120, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect($backUrl);
        $response->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

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
        Storage::fake('r2_private');
        $this->seedInvoiceFixture('invoice-admin-proof-webp-1', 100000);

        $backUrl = route('admin.procurement.supplier-invoices.index');

        $response = $this->actingAs($this->admin())
            ->from($backUrl)
            ->post(route('admin.procurement.supplier-invoices.payment-proof.store', [
                'supplierInvoiceId' => 'invoice-admin-proof-webp-1',
            ]), [
                'payment_invoice_id' => 'invoice-admin-proof-webp-1',
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-phone.webp', 5120, 'image/webp'),
                ],
            ]);

        $response->assertRedirect($backUrl);
        $response->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

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
        Storage::fake('r2_private');
        $this->seedInvoiceFixture('invoice-admin-proof-duplicate-1', 100000);
        $admin = $this->admin();

        $backUrl = route('admin.procurement.supplier-invoices.payment-proofs.show', [
            'supplierInvoiceId' => 'invoice-admin-proof-duplicate-1',
        ]);
        $route = route('admin.procurement.supplier-invoices.payment-proof.store', [
            'supplierInvoiceId' => 'invoice-admin-proof-duplicate-1',
        ]);

        $first = $this->actingAs($admin)
            ->from($backUrl)
            ->post($route, [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-duplicate-1.pdf', 120, 'application/pdf'),
                ],
            ]);

        $first->assertRedirect($backUrl);
        $first->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

        $second = $this->actingAs($admin)
            ->from($backUrl)
            ->post($route, [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-duplicate-2.pdf', 120, 'application/pdf'),
                ],
            ]);

        $second->assertRedirect($backUrl);
        $second->assertSessionHasErrors(['supplier_payment_proof']);

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
        Storage::fake('r2_private');
        $this->seedInvoiceFixture('invoice-admin-proof-partial-1', 100000);

        $this->seedMinimalSupplierPayment(
            'payment-admin-proof-existing-1',
            'invoice-admin-proof-partial-1',
            35000,
            '2026-05-12',
            'uploaded'
        );

        $backUrl = route('admin.procurement.supplier-invoices.payment-proofs.show', [
            'supplierInvoiceId' => 'invoice-admin-proof-partial-1',
        ]);

        $response = $this->actingAs($this->admin())
            ->from($backUrl)
            ->post(route('admin.procurement.supplier-invoices.payment-proof.store', [
                'supplierInvoiceId' => 'invoice-admin-proof-partial-1',
            ]), [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-partial.pdf', 120, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect($backUrl);
        $response->assertSessionHas('success', 'Bukti pembayaran supplier berhasil diunggah.');

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
        Storage::fake('r2_private');
        $this->seedInvoiceFixture('invoice-admin-proof-voided-1', 100000);

        DB::table('supplier_invoices')
            ->where('id', 'invoice-admin-proof-voided-1')
            ->update([
                'voided_at' => '2026-05-13 10:00:00',
                'void_reason' => 'Salah input.',
            ]);

        $backUrl = route('admin.procurement.supplier-invoices.payment-proofs.show', [
            'supplierInvoiceId' => 'invoice-admin-proof-voided-1',
        ]);

        $response = $this->actingAs($this->admin())
            ->from($backUrl)
            ->post(route('admin.procurement.supplier-invoices.payment-proof.store', [
                'supplierInvoiceId' => 'invoice-admin-proof-voided-1',
            ]), [
                'proof_files' => [
                    UploadedFile::fake()->create('proof-admin-voided.pdf', 120, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect($backUrl);
        $response->assertSessionHasErrors(['supplier_payment_proof']);

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
            'supplier-' . $suffix,
            'PT Supplier Admin Proof',
            'pt supplier admin proof'
        );

        $this->seedMinimalProduct(
            'product-' . $suffix,
            'KB-ADMIN-PROOF',
            'Ban Admin Proof',
            'Federal',
            100,
            75000
        );

        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-' . $suffix,
            '2026-05-11',
            '2026-05-21',
            $grandTotalRupiah,
            'PT Supplier Admin Proof'
        );

        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-' . $suffix,
            $invoiceId,
            'product-' . $suffix,
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
