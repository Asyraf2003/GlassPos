<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class AdminSupplierInvoiceTransactionCapabilityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_without_transaction_capability_can_still_read_procurement_invoice_pages(): void
    {
        $admin = $this->adminWithoutTransactionCapability();

        $this->actingAs($admin)
            ->get(route('admin.procurement.supplier-invoices.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.procurement.supplier-invoices.create'))
            ->assertOk();
    }

    public function test_admin_without_transaction_capability_is_rejected_from_supplier_invoice_creation(): void
    {
        $admin = $this->adminWithoutTransactionCapability();

        $this->seedMinimalProduct('product-027', 'PR-027', 'Produk 027', 'Federal', 100, 15000);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.procurement.supplier-invoices.store'), $this->validSupplierInvoicePayload());

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Admin belum diizinkan input transaksi.',
            'errors' => [
                'capability' => ['ADMIN_TRANSACTION_CAPABILITY_DISABLED'],
            ],
        ]);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
        $this->assertDatabaseCount('product_inventory_costing', 0);
    }

    public function test_admin_with_transaction_capability_can_create_supplier_invoice(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedMinimalProduct('product-027', 'PR-027', 'Produk 027', 'Federal', 100, 15000);

        $response = $this->actingAs($admin)
            ->post(route('admin.procurement.supplier-invoices.store'), $this->validSupplierInvoicePayload());

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));

        $this->assertDatabaseHas('suppliers', [
            'nama_pt_pengirim' => 'PT Supplier 027',
            'nama_pt_pengirim_normalized' => 'pt supplier 027',
        ]);

        $supplier = DB::table('suppliers')
            ->where('nama_pt_pengirim_normalized', 'pt supplier 027')
            ->first();

        $this->assertNotNull($supplier);

        $this->assertDatabaseHas('supplier_invoices', [
            'supplier_id' => (string) $supplier->id,
            'nomor_faktur' => 'INV-027-001',
            'nomor_faktur_normalized' => 'inv-027-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'tanggal_pengiriman' => '2026-05-10',
            'jatuh_tempo' => '2026-06-10',
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 1,
        ]);

        $invoice = DB::table('supplier_invoices')
            ->where('nomor_faktur_normalized', 'inv-027-001')
            ->first();

        $this->assertNotNull($invoice);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'supplier_invoice_id' => (string) $invoice->id,
            'line_no' => 1,
            'product_id' => 'product-027',
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => (string) $invoice->id,
            'revision_no' => 1,
            'event_name' => 'supplier_invoice_created',
        ]);

        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 1);
        $this->assertDatabaseCount('supplier_receipt_lines', 1);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-027',
            'movement_type' => 'stock_in',
            'tanggal_mutasi' => '2026-05-10',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 20000,
        ]);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-027',
            'qty_on_hand' => 2,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validSupplierInvoicePayload(): array
    {
        return [
            'nomor_faktur' => 'INV-027-001',
            'nama_pt_pengirim' => 'PT Supplier 027',
            'tanggal_pengiriman' => '2026-05-10',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-027',
                    'product_kode_barang_snapshot' => 'PR-027',
                    'product_nama_barang_snapshot' => 'Produk 027',
                    'product_merek_snapshot' => 'Federal',
                    'product_ukuran_snapshot' => 100,
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ];
    }

    private function adminWithoutTransactionCapability(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Procurement No Capability',
            'email' => 'admin-procurement-no-capability@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
