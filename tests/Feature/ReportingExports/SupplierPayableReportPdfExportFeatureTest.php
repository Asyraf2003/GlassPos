<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierPayableReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_supplier_payable_report_as_pdf(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);

        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplier('supplier-2', 'PT Sentosa Jaya');
        $this->seedSupplier('supplier-outside', 'PT Outside');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', 'F-001', '2030-01-07', '2030-01-20', 100000);
        $this->seedSupplierInvoice('invoice-2', 'supplier-2', 'F-002', '2030-01-09', '2030-01-31', 50000);
        $this->seedSupplierInvoice('invoice-outside', 'supplier-outside', 'F-OUT', '2030-02-01', '2030-02-10', 90000);

        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-2', 'product-1', 5, 50000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-outside', 'invoice-outside', 'product-1', 9, 90000, 10000);

        $this->seedSupplierPayment('payment-1', 'invoice-1', 60000, '2030-01-07', 'pending');
        $this->seedSupplierPayment('payment-2', 'invoice-1', 10000, '2030-01-10', 'uploaded');
        $this->seedSupplierPayment('payment-3', 'invoice-2', 50000, '2030-01-09', 'pending');
        $this->seedSupplierPayment('payment-outside', 'invoice-outside', 10000, '2030-02-01', 'uploaded');

        $this->seedSupplierReceipt('receipt-1', 'invoice-1', '2030-01-07');
        $this->seedSupplierReceipt('receipt-2', 'invoice-1', '2030-01-08');
        $this->seedSupplierReceipt('receipt-3', 'invoice-2', '2030-01-09');
        $this->seedSupplierReceipt('receipt-outside', 'invoice-outside', '2030-02-01');

        $this->seedSupplierReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-2', 'receipt-2', 'invoice-line-1', 1);
        $this->seedSupplierReceiptLine('receipt-line-3', 'receipt-3', 'invoice-line-2', 5);
        $this->seedSupplierReceiptLine('receipt-line-outside', 'receipt-outside', 'invoice-line-outside', 9);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-hutang-pemasok-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_supplier_payable_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.supplier_payable.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_supplier_payable_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_supplier_payable_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.supplier_payable.export_pdf', [
            'title' => 'Hutang Pemasok',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'referenceDateLabel' => '31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Faktur', 'value' => 2],
                ['label' => 'Total Tagihan', 'value' => 'Rp 150.000'],
                ['label' => 'Total Dibayar', 'value' => 'Rp 120.000'],
                ['label' => 'Outstanding', 'value' => 'Rp 30.000'],
            ],
            'periodRows' => [
                [
                    'period_label' => '07 Januari 2030',
                    'total_rows' => 1,
                    'grand_total' => 'Rp 100.000',
                    'total_paid' => 'Rp 70.000',
                    'outstanding' => 'Rp 30.000',
                ],
            ],
            'supplierRows' => [
                [
                    'supplier' => 'supplier-1',
                    'total_rows' => 1,
                    'grand_total' => 'Rp 100.000',
                    'total_paid' => 'Rp 70.000',
                    'outstanding' => 'Rp 30.000',
                ],
            ],
            'rows' => [
                [
                    'invoice_no' => 'F-001',
                    'supplier' => 'PT Sumber Makmur',
                    'shipment_date' => '07 Januari 2030',
                    'due_date' => '20 Januari 2030',
                    'status' => 'Lewat Jatuh Tempo',
                    'grand_total' => 'Rp 100.000',
                    'total_paid' => 'Rp 70.000',
                    'outstanding' => 'Rp 30.000',
                    'receipt_count' => 2,
                    'total_received_qty' => 3,
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Hutang Pemasok', $html);
        $this->assertStringContainsString('Total Tagihan', $html);
        $this->assertStringContainsString('Outstanding', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringContainsString('Catatan Laporan', $html);
        $this->assertStringContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Rincian Pemasok', $html);
        $this->assertStringNotContainsString('F-001', $html);
    }

    public function test_supplier_payable_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.supplier_payable.export_pdf', [
            'title' => 'Hutang Pemasok',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'referenceDateLabel' => '31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Faktur', 'value' => 2],
                ['label' => 'Total Tagihan', 'value' => 'Rp 150.000'],
                ['label' => 'Total Dibayar', 'value' => 'Rp 120.000'],
                ['label' => 'Outstanding', 'value' => 'Rp 30.000'],
            ],
            'periodRows' => [],
            'supplierRows' => [],
            'rows' => [
                [
                    'invoice_no' => 'F-001',
                    'supplier' => 'PT Sumber Makmur',
                    'shipment_date' => '07 Januari 2030',
                    'due_date' => '20 Januari 2030',
                    'status' => 'Lewat Jatuh Tempo',
                    'grand_total' => 'Rp 100.000',
                    'total_paid' => 'Rp 70.000',
                    'outstanding' => 'Rp 30.000',
                    'receipt_count' => 2,
                    'total_received_qty' => 3,
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringContainsString('Catatan Laporan', $html);
        $this->assertStringContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('<table class="summary">', $html);
        $this->assertStringNotContainsString('<table class="detail">', $html);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-supplier-payable-report-pdf-export@example.test',
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
            'nama_barang_normalized' => mb_strtolower(trim($namaBarang)),
            'merek' => $merek,
            'merek_normalized' => mb_strtolower(trim($merek)),
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function seedSupplier(string $id, string $namaPtPengirim): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => strtolower($namaPtPengirim),
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function seedSupplierInvoice(
        string $id,
        string $supplierId,
        string $nomorFaktur,
        string $shipmentDate,
        string $dueDate,
        int $grandTotalRupiah
    ): void {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'nomor_faktur' => $nomorFaktur,
            'nomor_faktur_normalized' => strtolower($nomorFaktur),
            'supplier_id' => $supplierId,
            'supplier_nama_pt_pengirim_snapshot' => DB::table('suppliers')->where('id', $supplierId)->value('nama_pt_pengirim'),
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
            'product_kode_barang_snapshot' => (string) DB::table('products')->where('id', $productId)->value('kode_barang'),
            'product_nama_barang_snapshot' => (string) DB::table('products')->where('id', $productId)->value('nama_barang'),
            'product_merek_snapshot' => (string) DB::table('products')->where('id', $productId)->value('merek'),
            'product_ukuran_snapshot' => DB::table('products')->where('id', $productId)->value('ukuran'),
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
