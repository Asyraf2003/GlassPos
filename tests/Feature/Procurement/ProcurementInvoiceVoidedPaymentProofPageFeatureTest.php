<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcurementInvoiceVoidedPaymentProofPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_voided_payment_proof_page_as_read_only(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');
        $this->seedVoidedInvoice('invoice-1', 'supplier-1');
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);
        $this->seedSupplierPayment('payment-1', 'invoice-1', 5000, '2026-03-16', 'uploaded');
        $this->seedSupplierPaymentProofAttachment(
            'attachment-1',
            'payment-1',
            'supplier-payment-proofs/payment-1/proof.pdf',
            'proof.pdf',
            'application/pdf',
            1000,
            '2026-03-16 10:00:00',
            'actor-admin',
        );

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();
        $response->assertSee('Halaman pembayaran bersifat baca-saja.');
        $response->assertSee('proof.pdf');
        $response->assertSee('Lihat PDF');
        $response->assertSee('Unduh');
        $response->assertDontSee('Simpan Pembayaran');
        $response->assertDontSee('Unggah Bukti');
        $response->assertDontSee('Kirim Bukti Pembayaran');
        $response->assertDontSee('Kirim Bukti &amp; Tandai Lunas', false);
    }

    private function seedVoidedInvoice(string $id, string $supplierId): void
    {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
            'nomor_faktur' => 'INV-SUP-VOID-001',
            'nomor_faktur_normalized' => 'inv-sup-void-001',
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'grand_total_rupiah' => 20000,
            'voided_at' => '2026-03-16 10:00:00',
            'void_reason' => 'Salah input sebelum ada efek domain.',
            'last_revision_no' => 1,
        ]);
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'merek' => $merek,
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
        ]);
    }

    private function seedSupplier(string $id, string $namaPtPengirim, string $namaPtPengirimNormalized): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => $namaPtPengirimNormalized,
        ]);
    }

    private function seedSupplierInvoiceLine(
        string $id,
        string $supplierInvoiceId,
        string $productId,
        int $qtyPcs,
        int $lineTotalRupiah,
        int $unitCostRupiah
    ): void {
        DB::table('supplier_invoice_lines')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'line_no' => 1,
            'product_id' => $productId,
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => $qtyPcs,
            'line_total_rupiah' => $lineTotalRupiah,
            'unit_cost_rupiah' => $unitCostRupiah,
        ]);
    }

    private function seedSupplierPayment(
        string $id,
        string $supplierInvoiceId,
        int $amountRupiah,
        string $paidAt,
        string $proofStatus
    ): void {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
            'proof_status' => $proofStatus,
            'proof_storage_path' => null,
        ]);
    }

    private function seedSupplierPaymentProofAttachment(
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

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-voided-payment-proof@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
