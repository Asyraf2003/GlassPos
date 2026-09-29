<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetSupplierPayableSummaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_supplier_payable_summary_handler_returns_invoice_level_rows_and_passes_reconciliation(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);
        $this->seedProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 50000);

        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplier('supplier-2', 'PT Sentosa Jaya');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', 'F-001', '2026-03-15', '2026-04-15', 100000);
        $this->seedSupplierInvoice('invoice-2', 'supplier-2', 'F-002', '2026-03-16', '2026-04-16', 50000);
        $this->seedSupplierInvoice('invoice-3', 'supplier-1', 'F-003', '2026-03-18', '2026-04-18', 30000);

        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-1', 'product-2', 1, 50000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-3', 'invoice-2', 'product-1', 5, 50000, 10000);

        $this->seedSupplierPayment('payment-1', 'invoice-1', 60000, '2026-03-15', 'pending');
        $this->seedSupplierPayment('payment-2', 'invoice-1', 10000, '2026-03-20', 'uploaded');
        $this->seedSupplierPayment('payment-3', 'invoice-2', 50000, '2026-03-16', 'pending');
        $this->seedSupplierPayment('payment-4', 'invoice-3', 30000, '2026-03-18', 'uploaded');

        $this->seedSupplierReceipt('receipt-1', 'invoice-1', '2026-03-15');
        $this->seedSupplierReceipt('receipt-2', 'invoice-1', '2026-03-16');
        $this->seedSupplierReceipt('receipt-3', 'invoice-2', '2026-03-16');

        $this->seedSupplierReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-2', 'receipt-2', 'invoice-line-2', 1);
        $this->seedSupplierReceiptLine('receipt-line-3', 'receipt-3', 'invoice-line-3', 5);

        $result = app(GetSupplierPayableSummaryHandler::class)
            ->handle('2026-03-15', '2026-03-16', '2026-03-16');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('rows', $data);

        $this->assertSame([
            [
                'supplier_invoice_id' => 'invoice-1',
                'nomor_faktur' => 'F-001',
                'supplier_id' => 'supplier-1',
                'supplier_name' => 'PT Sumber Makmur',
                'shipment_date' => '2026-03-15',
                'due_date' => '2026-04-15',
                'grand_total_rupiah' => 100000,
                'total_paid_rupiah' => 60000,
                'outstanding_rupiah' => 40000,
                'receipt_count' => 2,
                'total_received_qty' => 3,
                'due_status' => 'not_due',
                'due_status_label' => 'Belum Jatuh Tempo',
                'opening_outstanding_rupiah' => 0,
                'new_invoices_rupiah' => 100000,
                'adjustments_in_period_rupiah' => 0,
                'payments_in_period_rupiah' => 60000,
                'reversals_in_period_rupiah' => 0,
                'voided_as_of' => false,
                'history_basis' => 'legacy_shipment_date',
            ],
            [
                'supplier_invoice_id' => 'invoice-2',
                'nomor_faktur' => 'F-002',
                'supplier_id' => 'supplier-2',
                'supplier_name' => 'PT Sentosa Jaya',
                'shipment_date' => '2026-03-16',
                'due_date' => '2026-04-16',
                'grand_total_rupiah' => 50000,
                'total_paid_rupiah' => 50000,
                'outstanding_rupiah' => 0,
                'receipt_count' => 1,
                'total_received_qty' => 5,
                'due_status' => 'settled',
                'due_status_label' => 'Lunas',
                'opening_outstanding_rupiah' => 0,
                'new_invoices_rupiah' => 50000,
                'adjustments_in_period_rupiah' => 0,
                'payments_in_period_rupiah' => 50000,
                'reversals_in_period_rupiah' => 0,
                'voided_as_of' => false,
                'history_basis' => 'legacy_shipment_date',
            ],
        ], $data['rows']);
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
