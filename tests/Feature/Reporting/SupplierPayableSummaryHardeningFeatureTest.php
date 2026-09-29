<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierPayableSummaryHardeningFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_payable_summary_period_parity_matches_expected_totals_and_does_not_filter_payment_by_proof_status(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplier('supplier-2', 'PT Sentosa Jaya');
        $this->seedSupplier('supplier-3', 'PT Luar Scope');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2030-01-07', '2030-02-07', 100000);
        $this->seedSupplierInvoice('invoice-2', 'supplier-2', '2030-01-09', '2030-02-09', 50000);
        $this->seedSupplierInvoice('invoice-3', 'supplier-3', '2030-02-01', '2030-03-01', 30000);

        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-2', 'product-1', 5, 50000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-3', 'invoice-3', 'product-1', 3, 30000, 10000);

        $this->seedSupplierPayment('payment-1', 'invoice-1', 60000, '2030-01-07', 'pending');
        $this->seedSupplierPayment('payment-2', 'invoice-1', 10000, '2030-01-10', 'uploaded');
        $this->seedSupplierPayment('payment-3', 'invoice-2', 50000, '2030-01-09', 'pending');
        $this->seedSupplierPayment('payment-4', 'invoice-3', 30000, '2030-02-01', 'uploaded');

        $this->seedSupplierReceipt('receipt-1', 'invoice-1', '2030-01-07');
        $this->seedSupplierReceipt('receipt-2', 'invoice-1', '2030-01-08');
        $this->seedSupplierReceipt('receipt-3', 'invoice-2', '2030-01-09');

        $this->seedSupplierReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-2', 'receipt-2', 'invoice-line-1', 1);
        $this->seedSupplierReceiptLine('receipt-line-3', 'receipt-3', 'invoice-line-2', 5);

        $daily = $this->summaryTotals('2030-01-07', '2030-01-07');
        $weekly = $this->summaryTotals('2030-01-07', '2030-01-13');
        $monthly = $this->summaryTotals('2030-01-01', '2030-01-31');
        $custom = $this->summaryTotals('2030-01-01', '2030-01-31');

        $this->assertSame([
            'total_rows' => 1,
            'grand_total_rupiah' => 100000,
            'total_paid_rupiah' => 60000,
            'outstanding_rupiah' => 40000,
            'receipt_count' => 1,
            'total_received_qty' => 2,
        ], $daily);

        $this->assertSame([
            'total_rows' => 2,
            'grand_total_rupiah' => 150000,
            'total_paid_rupiah' => 120000,
            'outstanding_rupiah' => 30000,
            'receipt_count' => 3,
            'total_received_qty' => 8,
        ], $weekly);

        $this->assertSame([
            'total_rows' => 2,
            'grand_total_rupiah' => 150000,
            'total_paid_rupiah' => 120000,
            'outstanding_rupiah' => 30000,
            'receipt_count' => 3,
            'total_received_qty' => 8,
        ], $monthly);

        $this->assertSame($monthly, $custom);
    }

    private function summaryTotals(string $from, string $to): array
    {
        $result = app(GetSupplierPayableSummaryHandler::class)->handle($from, $to, $to);

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $rows = $data['rows'] ?? [];
        $this->assertIsArray($rows);

        return [
            'total_rows' => count($rows),
            'grand_total_rupiah' => array_sum(array_column($rows, 'grand_total_rupiah')),
            'total_paid_rupiah' => array_sum(array_column($rows, 'total_paid_rupiah')),
            'outstanding_rupiah' => array_sum(array_column($rows, 'outstanding_rupiah')),
            'receipt_count' => array_sum(array_column($rows, 'receipt_count')),
            'total_received_qty' => array_sum(array_column($rows, 'total_received_qty')),
        ];
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
        string $shipmentDate,
        string $dueDate,
        int $grandTotalRupiah
    ): void {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
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
