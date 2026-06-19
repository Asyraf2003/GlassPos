<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcurementInvoicePaymentProofPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_procurement_invoice_payment_proof_page(): void
    {
        $this->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_procurement_invoice_payment_proof_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_back_to_procurement_index_when_payment_proof_page_invoice_is_not_found(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'missing-invoice']));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));
        $response->assertSessionHas('error', 'Nota supplier tidak ditemukan.');
    }

    public function test_admin_can_access_payment_proof_page_with_payment_status_and_attachments(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedSupplier('supplier-1', 'PT Supplier Baru', 'pt supplier baru');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 150000, 'PT Sumber Makmur');
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);

        $this->seedSupplierPayment('payment-1', 'invoice-1', 50000, '2026-03-16', 'pending');
        $this->seedSupplierPayment('payment-2', 'invoice-1', 25000, '2026-03-17', 'uploaded', null);

        $this->seedSupplierPaymentProofAttachment(
            'attachment-1',
            'payment-2',
            'supplier-payment-proofs/payment-2/proof.pdf',
            'proof.pdf',
            'application/pdf',
            120000,
            '2026-03-17 10:00:00',
            'actor-admin',
        );

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();

        $response->assertSee('Header Faktur');
        $response->assertSee('Status Pembayaran');
        $response->assertSee('Sebagian Dibayar');
        $response->assertSee('Jumlah Pembayaran');
        $response->assertSee('2');

        $response->assertSee('Nomor Faktur');
        $response->assertSee('ID Nota Internal');
        $response->assertSee('Nama Pemasok Saat Ini');
        $response->assertSee('PT Supplier Baru');
        $response->assertSee('Nama Saat Nota Dibuat');
        $response->assertSee('PT Sumber Makmur');
        $response->assertSee('2026-03-15');
        $response->assertSee('2026-04-15');

        $response->assertSee('Rp 150.000');
        $response->assertSee('Rp 75.000');

        $response->assertSee('Kirim Bukti Pembayaran');
        $response->assertSee('Kirim Bukti & Tandai Lunas', false);
        $response->assertSee('.jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf', false);
        $response->assertSee('Maksimal 3 file. Format: JPG, JPEG, PNG, WEBP, HEIC, HEIF, PDF. Maksimal 10 MB per file.');
        $response->assertSee('invoice-payment-proof-submit', false);
        $response->assertSee('data-submitting-label="Mengirim..."', false);
        $response->assertSee('Bukti Pembayaran');
        $response->assertDontSee('Catat Pembayaran');
        $response->assertDontSee('Simpan Pembayaran');
        $response->assertDontSee('Nominal Pembayaran');
        $response->assertSee('Upload Bukti Pembayaran');
        $response->assertSee('payment_proof_files_0', false);
        $response->assertSee(route('admin.procurement.supplier-payments.proof.store', ['supplierPaymentId' => 'payment-1']), false);
        $response->assertSee('Maksimal 3 file. Format: JPG, JPEG, PNG, WEBP, HEIC, HEIF, PDF. Maksimal 10 MB per file.');

        $response->assertSee('Sudah Ada Bukti');
        $response->assertSee('Belum Ada Bukti');
        $response->assertSee('Jumlah Lampiran');
        $response->assertSee('Riwayat Lampiran');
        $response->assertSee('proof.pdf');
        $response->assertSee('Lihat PDF');
        $response->assertSee('Unduh');
    }

    public function test_admin_can_access_payment_proof_page_when_no_payment_exists(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 20000);
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();

        $response->assertSee('Status Pembayaran');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Jumlah Pembayaran');
        $response->assertSee('0');
        $response->assertSee('Kirim Bukti Pembayaran');
        $response->assertSee('Kirim Bukti & Tandai Lunas', false);
        $response->assertSee('.jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf', false);
        $response->assertSee('Maksimal 3 file. Format: JPG, JPEG, PNG, WEBP, HEIC, HEIF, PDF. Maksimal 10 MB per file.');
        $response->assertSee('Bukti Pembayaran');
        $response->assertSee('Belum ada pembayaran pemasok.');
        $response->assertDontSee('Catat Pembayaran');
        $response->assertDontSee('Simpan Pembayaran');
        $response->assertDontSee('Nominal Pembayaran');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-payment-proof@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
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

    private function seedSupplier(
        string $id,
        string $namaPtPengirim,
        string $namaPtPengirimNormalized
    ): void {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => $namaPtPengirimNormalized,
        ]);
    }

    private function seedSupplierInvoice(
        string $id,
        string $supplierId,
        string $shipmentDate,
        string $dueDate,
        int $grandTotalRupiah,
        string $supplierNamaPtPengirimSnapshot = 'PT Sumber Makmur'
    ): void {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'supplier_nama_pt_pengirim_snapshot' => $supplierNamaPtPengirimSnapshot,
            'tanggal_pengiriman' => $shipmentDate,
            'jatuh_tempo' => $dueDate,
            'grand_total_rupiah' => $grandTotalRupiah,
        ]);
    }

    private function seedSupplierInvoiceLine(
        string $id,
        string $supplierInvoiceId,
        string $productId,
        int $qtyPcs,
        int $lineTotalRupiah,
        int $unitCostRupiah,
        ?string $productKodeBarangSnapshot = 'KB-001',
        string $productNamaBarangSnapshot = 'Ban Luar',
        string $productMerekSnapshot = 'Federal',
        ?int $productUkuranSnapshot = 90,
        ?int $lineNo = null
    ): void {
        $resolvedLineNo = $lineNo
            ?? ((int) (DB::table('supplier_invoice_lines')
                ->where('supplier_invoice_id', $supplierInvoiceId)
                ->max('line_no') ?? 0) + 1);

        DB::table('supplier_invoice_lines')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'line_no' => $resolvedLineNo,
            'product_id' => $productId,
            'product_kode_barang_snapshot' => $productKodeBarangSnapshot,
            'product_nama_barang_snapshot' => $productNamaBarangSnapshot,
            'product_merek_snapshot' => $productMerekSnapshot,
            'product_ukuran_snapshot' => $productUkuranSnapshot,
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
        string $proofStatus,
        ?string $proofStoragePath = null
    ): void {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
            'proof_status' => $proofStatus,
            'proof_storage_path' => $proofStoragePath,
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
}
