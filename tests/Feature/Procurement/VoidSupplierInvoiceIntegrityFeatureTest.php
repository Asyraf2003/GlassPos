<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VoidSupplierInvoiceIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_editable_supplier_invoice_does_not_create_inventory_or_costing_side_effects(): void
    {
        $this->seedEditableInvoice();

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 43210,
            'inventory_value_rupiah' => 302470,
        ]);

        $beforeInventoryMovementCount = DB::table('inventory_movements')->count();
        $beforeReceiptCount = DB::table('supplier_receipts')->count();
        $beforePaymentCount = DB::table('supplier_payments')->count();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Salah input sebelum ada efek domain.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Nota supplier berhasil dibatalkan.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'void_reason' => 'Salah input sebelum ada efek domain.',
        ]);

        self::assertNotNull(
            DB::table('supplier_invoices')->where('id', 'invoice-1')->value('voided_at')
        );

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');

        self::assertSame($beforeInventoryMovementCount, DB::table('inventory_movements')->count());
        self::assertSame($beforeReceiptCount, DB::table('supplier_receipts')->count());
        self::assertSame($beforePaymentCount, DB::table('supplier_payments')->count());

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 43210,
            'inventory_value_rupiah' => 302470,
        ]);
    }

    public function test_failed_void_with_one_rupiah_payment_preserves_financial_state_exactly(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 1,
            'paid_at' => '2026-03-16',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Coba batalkan walau sudah ada payment 1 rupiah.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'voided_at' => null,
            'void_reason' => null,
            'grand_total_rupiah' => 100000,
        ]);

        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 1,
        ]);

        $tableResponse = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', ['payment_status' => 'outstanding']));

        $tableResponse->assertOk();
        $tableResponse->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-1');
        $tableResponse->assertJsonPath('data.rows.0.total_paid_rupiah', 1);
        $tableResponse->assertJsonPath('data.rows.0.outstanding_rupiah', 99999);
        $tableResponse->assertJsonPath('data.rows.0.policy_state', 'locked');
    }

    public function test_failed_void_after_single_piece_receive_preserves_stock_and_avg_cost_exactly(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-1',
            'supplier_invoice_id' => 'invoice-1',
            'tanggal_terima' => '2026-03-16',
        ]);

        DB::table('supplier_receipt_lines')->insert([
            'id' => 'receipt-line-1',
            'supplier_receipt_id' => 'receipt-1',
            'supplier_invoice_line_id' => 'invoice-line-1',
            'qty_diterima' => 1,
        ]);

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');

        DB::table('inventory_movements')->insert([
            'id' => 'movement-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'receipt-line-1',
            'tanggal_mutasi' => '2026-03-16',
            'qty_delta' => 1,
            'unit_cost_rupiah' => 50000,
            'total_cost_rupiah' => 50000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 1,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 50000,
            'inventory_value_rupiah' => 50000,
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Coba batalkan walau sudah ada receipt 1 pcs.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'voided_at' => null,
            'void_reason' => null,
        ]);

        $this->assertDatabaseHas('supplier_receipts', [
            'id' => 'receipt-1',
            'supplier_invoice_id' => 'invoice-1',
        ]);

        $this->assertDatabaseHas('supplier_receipt_lines', [
            'id' => 'receipt-line-1',
            'qty_diterima' => 1,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'movement-1',
            'product_id' => 'product-1',
            'qty_delta' => 1,
            'unit_cost_rupiah' => 50000,
            'total_cost_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 1,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 50000,
            'inventory_value_rupiah' => 50000,
        ]);
    }

    public function test_successful_void_excludes_only_target_invoice_from_outstanding_views_and_payable_summary(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-2',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Test',
            'nomor_faktur' => 'INV-SUP-002',
            'nomor_faktur_normalized' => 'inv-sup-002',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-16',
            'grand_total_rupiah' => 200000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 0,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-2',
            'supplier_invoice_id' => 'invoice-2',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 100,
            'qty_pcs' => 4,
            'line_total_rupiah' => 200000,
            'unit_cost_rupiah' => 50000,
        ]);

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');
        $this->syncSupplierInvoiceProjectionForTest('invoice-2');

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Hanya invoice ini yang harus hilang dari hutang aktif.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Nota supplier berhasil dibatalkan.');

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');
        $this->syncSupplierInvoiceProjectionForTest('invoice-2');

        $tableResponse = $this->actingAs($this->admin())
            ->get(route('admin.procurement.supplier-invoices.table', ['payment_status' => 'outstanding']));

        $tableResponse->assertOk();
        $tableResponse->assertJsonCount(1, 'data.rows');
        $tableResponse->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-2');
        $tableResponse->assertJsonPath('data.rows.0.outstanding_rupiah', 200000);

        $rows = app(GetSupplierPayableSummaryHandler::class)
            ->handle(null, null, '2026-03-20')
            ->data()['rows'];

        self::assertCount(1, $rows);
    }

    private function seedEditableInvoice(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Supplier Test',
            'nama_pt_pengirim_normalized' => 'pt supplier test',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 75000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Test',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'grand_total_rupiah' => 100000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 0,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-1',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 100,
            'qty_pcs' => 2,
            'line_total_rupiah' => 100000,
            'unit_cost_rupiah' => 50000,
        ]);

        $this->syncSupplierInvoiceProjectionForTest('invoice-1');
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Void Integrity',
            'email' => 'admin-void-integrity-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
