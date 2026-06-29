<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcurementInvoiceDetailPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_procurement_invoice_detail_page(): void
    {
        $this->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_procurement_invoice_detail_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_back_to_procurement_index_when_invoice_detail_is_not_found(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'missing-invoice']));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));
        $response->assertSessionHas('error', 'Nota supplier tidak ditemukan.');
    }

    public function test_admin_can_access_procurement_invoice_detail_page_as_locked_when_receipt_and_payment_exist(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedProduct('product-2', null, 'Aki Kering', 'GS Astra', null, 120000);
        $this->seedSupplier('supplier-1', 'PT Supplier Baru', 'pt supplier baru');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 150000, 'PT Sumber Makmur');

        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-1', 'product-2', 1, 130000, 130000, null, 'Aki Kering', 'GS Astra', null);

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

        $this->seedSupplierReceipt('receipt-1', 'invoice-1', '2026-03-16');
        $this->seedSupplierReceipt('receipt-2', 'invoice-1', '2026-03-17');

        $this->seedSupplierReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-2', 'receipt-2', 'invoice-line-2', 1);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();

        $response->assertSee('Status Kebijakan');
        $response->assertSee('Locked');
        $response->assertSee('Aksi yang Diizinkan');
        $response->assertSee('Koreksi');
        $response->assertSee('Alasan Penguncian');
        $response->assertSee('Receipt sudah tercatat');
        $response->assertSee('Payment efektif sudah tercatat');

        $response->assertSee('Ringkasan Nota');
        $response->assertSee('Rincian Nota');
        $response->assertSee('Pembayaran');

        $response->assertSee('invoice-1');
        $response->assertSee('Nama Pemasok Saat Ini');
        $response->assertSee('PT Supplier Baru');
        $response->assertSee('Nama Saat Nota Dibuat');
        $response->assertSee('PT Sumber Makmur');
        $response->assertSee('2026-03-15');
        $response->assertSee('2026-04-15');

        $response->assertSee('Rp 150.000');
        $response->assertSee('Rp 75.000');
        $response->assertSee('Jumlah Penerimaan');
        $response->assertSee('2');
        $response->assertSee('Total Kuantitas Diterima');
        $response->assertSee('3');

        $response->assertSee('KB-001');
        $response->assertSee('Ban Luar');
        $response->assertSee('Federal');
        $response->assertSee('90');
        $response->assertSee('Rp 10.000');
        $response->assertSee('Rp 20.000');

        $response->assertSee('Aki Kering');
        $response->assertSee('GS Astra');
        $response->assertSee('Rp 130.000');

        $response->assertDontSee('Bukti Pembayaran');
        $response->assertDontSee('Unggah Bukti');
        $response->assertDontSee('Simpan Pembayaran');
    }

    public function test_admin_can_access_procurement_invoice_detail_page_as_editable_when_no_receipt_and_no_payment_exist(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 20000);
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();

        $response->assertSee('Status Kebijakan');
        $response->assertSee('Editable');
        $response->assertSee('Aksi yang Diizinkan');
        $response->assertSee('Edit nota');
        $response->assertSee('Hapus nota');
        $response->assertSee('Alasan Penguncian');
        $response->assertSee('Belum ada efek turunan primer.');
        $response->assertSee('Pembayaran');

        $response->assertDontSee('Bukti Pembayaran');
        $response->assertDontSee('Belum ada pembayaran pemasok.');
    }

    public function test_admin_can_see_latest_supplier_invoice_revision_reason_on_detail_page(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 20000);
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 20000, 10000);

        DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->update(['last_revision_no' => 2]);

        DB::table('supplier_invoice_versions')->insert([
            'id' => 'invoice-1-r002',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'event_name' => 'supplier_invoice_updated',
            'changed_by_actor_id' => 'actor-admin',
            'change_reason' => 'Koreksi qty dan pajak <script>alert("supplier")</script>.',
            'changed_at' => '2026-03-16 10:00:00',
            'snapshot_json' => json_encode(['supplier_invoice_id' => 'invoice-1'], JSON_THROW_ON_ERROR),
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();
        $response->assertSee('Perubahan Terakhir');
        $response->assertSee('Revisi 2');
        $response->assertSee('Alasan Perubahan Terakhir');
        $response->assertSee('Koreksi qty dan pajak &lt;script&gt;alert(&quot;supplier&quot;)&lt;/script&gt;.', false);
        $response->assertDontSee('Koreksi qty dan pajak <script>alert("supplier")</script>.', false);
        $response->assertDontSee('actor-admin');
    }

    public function test_admin_can_see_supplier_invoice_version_timeline_snapshots_on_detail_page(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000);
        $this->seedProduct('product-2', 'KB-002', 'Oli Mesin', 'Federal Oil', 1, 15000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 45000);
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 3, 30000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-1', 'product-2', 1, 15000, 15000, 'KB-002', 'Oli Mesin', 'Federal Oil', 1);

        DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->update(['last_revision_no' => 2]);

        DB::table('supplier_invoice_versions')->insert([
            [
                'id' => 'invoice-1-r001',
                'supplier_invoice_id' => 'invoice-1',
                'revision_no' => 1,
                'event_name' => 'supplier_invoice_created',
                'changed_by_actor_id' => 'actor-admin',
                'change_reason' => null,
                'changed_at' => '2026-03-15 02:00:00',
                'snapshot_json' => json_encode([
                    'nomor_faktur' => 'INV-SUP-001',
                    'supplier' => ['nama_pt_pengirim_snapshot' => 'PT Sumber Makmur'],
                    'tanggal_pengiriman' => '2026-03-15',
                    'jatuh_tempo' => '2026-04-15',
                    'subtotal_before_tax_rupiah' => 20000,
                    'tax_amount_rupiah' => 0,
                    'grand_total_rupiah' => 20000,
                    'lines' => [
                        [
                            'line_no' => 1,
                            'product_kode_barang_snapshot' => 'KB-001',
                            'product_nama_barang_snapshot' => 'Ban Luar',
                            'product_merek_snapshot' => 'Federal',
                            'product_ukuran_snapshot' => 90,
                            'qty_pcs' => 2,
                            'line_subtotal_before_tax_rupiah' => 20000,
                            'tax_amount_rupiah' => 0,
                            'line_total_rupiah' => 20000,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'id' => 'invoice-1-r002',
                'supplier_invoice_id' => 'invoice-1',
                'revision_no' => 2,
                'event_name' => 'supplier_invoice_updated',
                'changed_by_actor_id' => 'actor-admin',
                'change_reason' => 'Tambah oli dan koreksi qty <script>alert("invoice")</script>.',
                'changed_at' => '2026-03-16 02:00:00',
                'snapshot_json' => json_encode([
                    'nomor_faktur' => 'INV-SUP-001-REV',
                    'supplier' => ['nama_pt_pengirim_snapshot' => 'PT Sumber Makmur'],
                    'tanggal_pengiriman' => '2026-03-16',
                    'jatuh_tempo' => '2026-04-15',
                    'subtotal_before_tax_rupiah' => 45000,
                    'tax_amount_rupiah' => 0,
                    'grand_total_rupiah' => 45000,
                    'lines' => [
                        [
                            'line_no' => 1,
                            'product_kode_barang_snapshot' => 'KB-001',
                            'product_nama_barang_snapshot' => 'Ban Luar',
                            'product_merek_snapshot' => 'Federal',
                            'product_ukuran_snapshot' => 90,
                            'qty_pcs' => 3,
                            'line_subtotal_before_tax_rupiah' => 30000,
                            'tax_amount_rupiah' => 0,
                            'line_total_rupiah' => 30000,
                        ],
                        [
                            'line_no' => 2,
                            'product_kode_barang_snapshot' => 'KB-002',
                            'product_nama_barang_snapshot' => 'Oli Mesin',
                            'product_merek_snapshot' => 'Federal Oil',
                            'product_ukuran_snapshot' => 1,
                            'qty_pcs' => 1,
                            'line_subtotal_before_tax_rupiah' => 15000,
                            'tax_amount_rupiah' => 0,
                            'line_total_rupiah' => 15000,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ],
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertOk();
        $response->assertSee('Riwayat Versi Nota Pemasok');
        $response->assertSee('Ringkasan Perubahan');
        $response->assertSee('Lihat Detail Versi');
        $response->assertSee('data-bs-toggle="collapse"', false);
        $response->assertSeeText('Nomor Faktur: INV-SUP-001 → INV-SUP-001-REV');
        $response->assertSeeText('Tanggal Pengiriman: 2026-03-15 → 2026-03-16');
        $response->assertSeeText('Total Nota: Rp 20.000 → Rp 45.000');
        $response->assertSeeText('Ban Luar: Qty 2 → 3');
        $response->assertSeeText('Tambah Rincian: Oli Mesin, Qty 1, Total Rp 15.000');
        $response->assertSee('Revisi 2');
        $response->assertSee('Revisi 1');
        $response->assertSee('INV-SUP-001-REV');
        $response->assertSee('INV-SUP-001');
        $response->assertSee('Ban Luar');
        $response->assertSee('Oli Mesin');
        $response->assertSee('Rp 45.000');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Tambah oli dan koreksi qty &lt;script&gt;alert(&quot;invoice&quot;)&lt;/script&gt;.', false);
        $response->assertDontSee('Tambah oli dan koreksi qty <script>alert("invoice")</script>.', false);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '@example.test',
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

    private function seedSupplierReceipt(
        string $id,
        string $supplierInvoiceId,
        string $tanggalTerima
    ): void {
        DB::table('supplier_receipts')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'tanggal_terima' => $tanggalTerima,
        ]);
    }

    private function seedSupplierReceiptLine(
        string $id,
        string $supplierReceiptId,
        string $supplierInvoiceLineId,
        int $qtyDiterima
    ): void {
        DB::table('supplier_receipt_lines')->insert([
            'id' => $id,
            'supplier_receipt_id' => $supplierReceiptId,
            'supplier_invoice_line_id' => $supplierInvoiceLineId,
            'qty_diterima' => $qtyDiterima,
        ]);
    }
}
