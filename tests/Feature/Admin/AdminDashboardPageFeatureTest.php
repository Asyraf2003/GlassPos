<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminDashboardPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_admin_dashboard(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_dashboard_and_see_wired_report_metrics(): void
    {
        Carbon::setTestNow('2030-01-09 08:00:00');

        try {
            $this->seedDashboardFixtures();

            $response = $this->actingAs($this->user('admin'))
                ->get(route('admin.dashboard'));

            $response->assertOk();
            $response->assertSee('<main id="main"', false);
            $response->assertSee('role="main"', false);
            $response->assertSee('aria-label="Buka menu utama"', false);
            $response->assertSee('aria-label="Ganti tema gelap atau terang"', false);
            $response->assertSee('aria-label="Tutup menu utama"', false);
            $response->assertSee('Ringkasan Toko');
            $response->assertSee('Total Nilai Nota Bulan Ini');
            $response->assertSee('Rp 150.000');
            $response->assertSee('Uang Bersih Diterima Bulan Ini');
            $response->assertSee('Rp 111.000');
            $response->assertSee('Sisa Tagihan Bulan Ini');
            $response->assertSee('Rp 9.001');
            $response->assertSee('Total Stok Tersedia');
            $response->assertSee('21 Unit');
            $response->assertSee('Nilai Modal Stok');
            $response->assertSee('Rp 211.000');
            $response->assertSee('Status Stok Saat Ini');
            $response->assertSee('Cetak / Unduh Laporan');
            $response->assertSee(route('admin.reports.transaction_summary.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-09',
            ]));
            $response->assertSee(route('admin.reports.inventory_stock_value.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-09',
            ]));
            $response->assertSee(route('admin.dashboard.analytics'), false);
            $response->assertSee('admin-chart-operational-performance', false);
            $response->assertSee('data-dashboard-analytics-target="operational-summary"', false);
            $response->assertSee('Potensi Kembalian');
            $response->assertDontSee('admin-chart-cashflow-line', false);
            $response->assertSee('Prioritas Restok');
            $response->assertSee('Lihat Detail');
            $response->assertSee(route('admin.products.show', ['productId' => 'product-2']), false);
            $response->assertSee(route('admin.products.show', ['productId' => 'product-3']), false);
            $response->assertSee('Vario');
            $response->assertSee('Beat');
            $response->assertSee('Kritis');
            $response->assertSee('Mulai Perlu Restok');
            $response->assertSeeInOrder([
                'Prioritas Restok',
                'Vario',
                '3 Unit',
                '4',
                '3',
                'Kritis',
                'Beat',
                '5 Unit',
                '5',
                '2',
                'Mulai Perlu Restok',
            ]);
            $response->assertSee('Uang Diterima Hari Ini');
            $response->assertSee('Rp 50.000');
            $response->assertSee('Sisa Kas Operasional Bulan Ini');
            $response->assertDontSee('Net Cash Bulan Ini');
            $response->assertDontSee('Outstanding Bulan Ini');
            $response->assertDontSee('Total Qty On Hand');
            $response->assertDontSee('Aktivitas Ledger Periode Ini');
            $response->assertSee('Rp -74.000');
            $response->assertSee('Uang Dikembalikan Bulan Ini');
            $response->assertSee('Rp 5.000');
            $response->assertSee('Riwayat Uang dan Stok Bulan Ini');
            $response->assertSee('Uang Masuk Sebelum Refund');
            $response->assertSee('Uang Refund Keluar Bulan Ini');
            $response->assertSee('Barang Keluar Sebelum Barang Balik');
            $response->assertSee('Barang Keluar Bersih');
            $response->assertSee('Sisa Hutang Supplier');
            $response->assertSee('Rp 30.000');
            $response->assertSee('Kasbon/Hutang Karyawan');
            $response->assertSee('Rp 60.000');
            $response->assertSee('Biaya Operasional');
            $response->assertSee('Barang Paling Laku');
            $response->assertSee('Supra');
            $response->assertSee('Vario');
            $response->assertSee('2 Unit');
            $response->assertSee('1 Unit');
            $response->assertSee('Rp 17.000');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_fetch_dashboard_analytics_payload(): void
    {
        Carbon::setTestNow('2030-01-09 08:00:00');

        try {
            $this->seedDashboardFixtures();

            $response = $this->actingAs($this->user('admin'))
                ->getJson(route('admin.dashboard.analytics'));

            $response->assertOk();
            $response->assertJsonPath('charts.stock_status_donut.segments.0.label', 'Stok Aman');
            $response->assertJsonPath('charts.stock_status_donut.segments.0.value', 1);
            $response->assertJsonPath('charts.stock_status_donut.segments.1.label', 'Mulai Restok');
            $response->assertJsonPath('charts.stock_status_donut.segments.1.value', 1);
            $response->assertJsonPath('charts.stock_status_donut.segments.2.label', 'Stok Kritis');
            $response->assertJsonPath('charts.stock_status_donut.segments.2.value', 1);
            $response->assertJsonPath('charts.stock_status_donut.segments.3.label', 'Belum Diatur');
            $response->assertJsonPath('charts.stock_status_donut.segments.3.value', 1);
            $payload = $response->json();
            $this->assertArrayNotHasKey('cashflow_line', $payload['charts'] ?? []);
            $this->assertArrayHasKey('operational_performance_bar', $payload['charts'] ?? []);
            $response->assertJsonPath('charts.operational_performance_bar.summary.total_operational_expense_rupiah', 5000);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_view_dashboard_and_analytics_for_selected_month(): void
    {
        Carbon::setTestNow('2030-02-09 08:00:00');

        try {
            $this->seedDashboardFixtures();
            $admin = $this->user('admin');

            $response = $this->actingAs($admin)
                ->get(route('admin.dashboard', ['month' => '2030-01']));

            $response->assertOk();
            $response->assertSee('Bulan Dashboard');
            $response->assertSee('Filter &amp; Cetak', false);
            $response->assertSee('id="admin-dashboard-filter-drawer"', false);
            $response->assertSee('name="month"', false);
            $response->assertSee('value="2030-01"', false);
            $response->assertSee('Rp 150.000');
            $response->assertSee(route('admin.dashboard.analytics', ['month' => '2030-01']), false);

            $payload = $this->actingAs($admin)
                ->getJson(route('admin.dashboard.analytics', ['month' => '2030-01']));

            $payload->assertOk();
            $payload->assertJsonPath('period.active_month', '2030-01');
            $payload->assertJsonPath('period.date_from', '2030-01-01');
            $payload->assertJsonPath('period.date_to', '2030-01-31');
            $payload->assertJsonPath('charts.stock_status_donut.segments.0.value', 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function seedDashboardFixtures(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'LISTRIK', 'Listrik');
        $this->seedEmployee('employee-1', 'Montir A');
        $this->seedProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000, 5, 2);
        $this->seedProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000, 4, 3);
        $this->seedProduct('product-3', 'KB-003', 'Beat', 'Federal', 80, 16000, 5, 2);
        $this->seedProduct('product-4', 'KB-004', 'Scoopy', 'Federal', 85, 18000, null, null);

        $this->seedNote('note-1', 'Budi', '2030-01-07', 100000);
        $this->seedNote('note-2', 'Siti', '2030-01-09', 50000);
        $this->seedNote('note-3', 'Outside', '2030-02-01', 30000);

        $this->seedWorkItem('wi-1', 'note-1', 1, 'service_with_external_purchase', 100000);
        $this->seedWorkItem('wi-2', 'note-2', 1, 'store_stock_sale_only', 50000);
        $this->seedWorkItem('wi-3', 'note-3', 1, 'service_only', 30000);

        DB::table('work_item_external_purchase_lines')->insert([
            [
                'id' => 'epl-1',
                'work_item_id' => 'wi-1',
                'cost_description' => 'Part luar',
                'unit_cost_rupiah' => 20000,
                'qty' => 1,
                'line_total_rupiah' => 20000,
            ],
        ]);

        DB::table('work_item_store_stock_lines')->insert([
            [
                'id' => 'sto1',
                'work_item_id' => 'wi-1',
                'product_id' => 'product-1',
                'qty' => 2,
                'line_total_rupiah' => 30000,
            ],
            [
                'id' => 'sto2',
                'work_item_id' => 'wi-2',
                'product_id' => 'product-2',
                'qty' => 1,
                'line_total_rupiah' => 17000,
            ],
        ]);

        $this->seedCustomerPayment('payment-1', 70000, '2030-01-07');
        $this->seedCustomerPayment('payment-2', 50000, '2030-01-09');
        $this->seedCustomerPayment('payment-3', 30000, '2030-02-01');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
        $this->seedPaymentAllocation('allocation-3', 'payment-3', 'note-3', 30000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 70000,
                'allocation_priority' => 1,
            ],
        ]);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 9000, '2030-01-08', 'Koreksi');
        $this->seedCustomerRefund('refund-2', 'payment-3', 'note-3', 3000, '2030-02-01', 'Outside');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-1',
                'customer_refund_id' => 'refund-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'refunded_amount_rupiah' => 5000,
                'refund_priority' => 1,
            ],
        ]);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr1',
                'tanggal_mutasi' => '2030-01-07',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'm2',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'sto1',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -40000,
            ],
            [
                'id' => 'm3',
                'product_id' => 'product-2',
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => 'sto2',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => -1,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -10000,
            ],
            [
                'id' => 'm4',
                'product_id' => 'product-2',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'sr2',
                'tanggal_mutasi' => '2030-01-09',
                'qty_delta' => 4,
                'unit_cost_rupiah' => 11500,
                'total_cost_rupiah' => 46000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            ['product_id' => 'product-1', 'qty_on_hand' => 6],
            ['product_id' => 'product-2', 'qty_on_hand' => 3],
            ['product_id' => 'product-3', 'qty_on_hand' => 5],
            ['product_id' => 'product-4', 'qty_on_hand' => 7],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'product-1', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 60000],
            ['product_id' => 'product-2', 'avg_cost_rupiah' => 12000, 'inventory_value_rupiah' => 36000],
            ['product_id' => 'product-3', 'avg_cost_rupiah' => 9000, 'inventory_value_rupiah' => 45000],
            ['product_id' => 'product-4', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 70000],
        ]);

        DB::table('operational_expenses')->insert([
            [
                'id' => 'expense-1',
                'category_id' => 'expense-category-1',
                'category_code_snapshot' => 'LISTRIK',
                'category_name_snapshot' => 'Listrik',
                'amount_rupiah' => 5000,
                'expense_date' => '2030-01-08',
                'description' => 'Listrik',
                'payment_method' => 'cash',
                'reference_no' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('payroll_disbursements')->insert([
            [
                'id' => 'payroll-1',
                'employee_id' => 'employee-1',
                'amount' => 10000,
                'disbursement_date' => '2030-01-09 10:00:00',
                'mode' => 'weekly',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplierInvoice('invoice-1', 'supplier-1', '2030-01-07', '2030-02-07', 100000);
        $this->seedSupplierInvoiceLine('invoice-line-1', 'invoice-1', 'product-1', 2, 100000, 50000);
        $this->seedSupplierPayment('supplier-payment-1', 'invoice-1', 70000, '2030-01-07', 'uploaded');

        DB::table('employee_debts')->insert([
            [
                'id' => 'debt-1',
                'employee_id' => 'employee-1',
                'total_debt' => 100000,
                'remaining_balance' => 60000,
                'status' => 'unpaid',
                'notes' => 'Kasbon',
                'created_at' => '2030-01-08 08:00:00',
                'updated_at' => '2030-01-08 08:00:00',
            ],
        ]);

        DB::table('employee_debt_payments')->insert([
            [
                'id' => 'debt-payment-1',
                'employee_debt_id' => 'debt-1',
                'amount' => 40000,
                'payment_date' => '2030-01-09 09:00:00',
                'notes' => null,
                'created_at' => '2030-01-09 09:00:00',
                'updated_at' => '2030-01-09 09:00:00',
            ],
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-admin-dashboard@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedExpenseCategory(string $id, string $code, string $name): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedEmployee(string $id, string $name): void
    {
        DB::table('employees')->insert([
            'id' => $id,
            'employee_name' => $name,
            'phone' => '081234567890',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual,
        ?int $reorderPointQty,
        ?int $criticalThresholdQty,
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
            'reorder_point_qty' => $reorderPointQty,
            'critical_threshold_qty' => $criticalThresholdQty,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function seedNote(string $id, string $customerName, string $transactionDate, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedWorkItem(
        string $id,
        string $noteId,
        int $lineNo,
        string $transactionType,
        int $subtotalRupiah
    ): void {
        DB::table('work_items')->insert([
            'id' => $id,
            'note_id' => $noteId,
            'line_no' => $lineNo,
            'transaction_type' => $transactionType,
            'status' => 'open',
            'subtotal_rupiah' => $subtotalRupiah,
        ]);
    }

    private function seedCustomerPayment(string $id, int $amountRupiah, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(string $id, string $paymentId, string $noteId, int $amountRupiah): void
    {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
            'refunded_at' => $refundedAt,
            'reason' => $reason,
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
        int $unitCostRupiah
    ): void {
        DB::table('supplier_invoice_lines')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'line_no' => 1,
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
}
