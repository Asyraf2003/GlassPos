<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierPayableReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_supplier_payable_report_page(): void
    {
        $this->get(route('admin.reports.supplier_payable.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_supplier_payable_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.supplier_payable.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_supplier_payable_report_page_and_see_due_status_buckets(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplier('supplier-2', 'PT Sentosa Jaya');

        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2030-01-07', '2030-01-20', 100000);
        $this->seedSupplierInvoice('invoice-2', 'supplier-2', '2030-01-09', '2030-01-31', 50000);
        $this->seedSupplierInvoice('invoice-3', 'supplier-1', '2030-01-11', '2030-02-05', 20000);
        $this->seedSupplierInvoice('invoice-4', 'supplier-2', '2030-01-12', '2030-01-15', 30000);

        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-2', 'invoice-2', 'product-1', 5, 50000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-3', 'invoice-3', 'product-1', 2, 20000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-4', 'invoice-4', 'product-1', 3, 30000, 10000);

        $this->seedSupplierPayment('payment-1', 'invoice-1', 70000, '2030-01-07', 'pending');
        $this->seedSupplierPayment('payment-2', 'invoice-2', 40000, '2030-01-10', 'uploaded');
        $this->seedSupplierPayment('payment-3', 'invoice-4', 30000, '2030-01-12', 'pending');

        $this->seedSupplierReceipt('receipt-1', 'invoice-1', '2030-01-07');
        $this->seedSupplierReceipt('receipt-2', 'invoice-2', '2030-01-09');
        $this->seedSupplierReceipt('receipt-3', 'invoice-3', '2030-01-11');
        $this->seedSupplierReceipt('receipt-4', 'invoice-4', '2030-01-12');

        $this->seedSupplierReceiptLine('receipt-line-1', 'receipt-1', 'invoice-line-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-2', 'receipt-2', 'invoice-line-2', 5);
        $this->seedSupplierReceiptLine('receipt-line-3', 'receipt-3', 'invoice-line-3', 2);
        $this->seedSupplierReceiptLine('receipt-line-4', 'receipt-4', 'invoice-line-4', 3);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Hutang Pemasok');
        $response->assertSee('supplier-payable-report-filter-form', false);
        $response->assertSee('Notifikasi hutang faktur belum aktif.');
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Status jatuh tempo dievaluasi terhadap tanggal referensi 31 Januari 2030.');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Rp 140.000');
        $response->assertSee('Rp 60.000');
        $response->assertSee('Belum Jatuh Tempo');
        $response->assertSee('Jatuh Tempo Hari Ini');
        $response->assertSee('Lewat Jatuh Tempo');
        $response->assertSee('Lunas');
        $response->assertSee('Rp 30.000');
        $response->assertSee(route('admin.reports.transaction_cash_ledger.index'), false);
        $response->assertSee(route('admin.reports.employee_debt.index'), false);
        $response->assertSee(route('admin.reports.operational_profit.index'), false);
        $response->assertSee(route('admin.reports.supplier_payable.index'), false);
    }

    public function test_admin_sees_owner_readable_report_sections_on_supplier_payable_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertSee('Catatan Laporan');
        $response->assertSee('Detail lengkap tersedia di Excel');
    }

    public function test_custom_mode_uses_explicit_date_range_and_date_to_as_default_reference_date(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 50000);
        $this->seedSupplier('supplier-1', 'PT Custom A');
        $this->seedSupplier('supplier-2', 'PT Custom B');
        $this->seedSupplier('supplier-3', 'PT Outside');

        $this->seedSupplierInvoice('invoice-in-1', 'supplier-1', '2030-01-07', '2030-01-08', 100000);
        $this->seedSupplierInvoice('invoice-in-2', 'supplier-2', '2030-01-09', '2030-01-09', 50000);
        $this->seedSupplierInvoice('invoice-out', 'supplier-3', '2030-01-11', '2030-01-11', 90000);

        $this->seedSupplierInvoiceLine('invoice-line-in-1', 'invoice-in-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierInvoiceLine('invoice-line-in-2', 'invoice-in-2', 'product-1', 5, 50000, 10000);
        $this->seedSupplierInvoiceLine('invoice-line-out', 'invoice-out', 'product-1', 9, 90000, 10000);

        $this->seedSupplierPayment('payment-in-1', 'invoice-in-1', 40000, '2030-01-07', 'pending');

        $this->seedSupplierReceipt('receipt-in-1', 'invoice-in-1', '2030-01-07');
        $this->seedSupplierReceipt('receipt-in-2', 'invoice-in-2', '2030-01-09');
        $this->seedSupplierReceipt('receipt-out', 'invoice-out', '2030-01-11');

        $this->seedSupplierReceiptLine('receipt-line-in-1', 'receipt-in-1', 'invoice-line-in-1', 2);
        $this->seedSupplierReceiptLine('receipt-line-in-2', 'receipt-in-2', 'invoice-line-in-2', 5);
        $this->seedSupplierReceiptLine('receipt-line-out', 'receipt-out', 'invoice-line-out', 9);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-07',
                'date_to' => '2030-01-09',
            ])
        );

        $response->assertOk();
        $response->assertSee('07 Januari 2030 s/d 09 Januari 2030');
        $response->assertSee('Status jatuh tempo dievaluasi terhadap tanggal referensi 09 Januari 2030.');
        $response->assertSee('Rp 150.000');
        $response->assertSee('Rp 40.000');
        $response->assertSee('Rp 110.000');
        $response->assertSee('invoice-in-1');
        $response->assertSee('invoice-in-2');
        $response->assertDontSee('invoice-out');
    }

    public function test_custom_mode_requires_explicit_date_range(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.index', [
                'period_mode' => 'custom',
            ])
        );

        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_custom_mode_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.supplier_payable.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-10',
                'date_to' => '2030-01-01',
            ])
        );

        $response->assertSessionHasErrors(['date_from']);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-supplier-payable-report@example.test',
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
