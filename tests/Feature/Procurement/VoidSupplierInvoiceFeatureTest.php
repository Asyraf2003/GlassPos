<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VoidSupplierInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_void_editable_supplier_invoice_with_reason(): void
    {
        $this->seedEditableInvoice();

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

        $this->assertNotNull(
            DB::table('supplier_invoices')->where('id', 'invoice-1')->value('voided_at')
        );
    }

    public function test_admin_cannot_void_supplier_invoice_without_reason(): void
    {
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => '   ',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['void_reason']);
    }

    public function test_admin_cannot_void_supplier_invoice_when_receipt_already_exists(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-1',
            'supplier_invoice_id' => 'invoice-1',
            'tanggal_terima' => '2026-03-16',
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Tidak boleh lagi karena sudah ada receipt.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_invoice']);
    }

    public function test_admin_cannot_void_supplier_invoice_when_payment_already_exists(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 10000,
            'paid_at' => '2026-03-16',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.void', ['supplierInvoiceId' => 'invoice-1']), [
                'void_reason' => 'Tidak boleh lagi karena sudah ada payment.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_invoice']);
    }

    public function test_voided_supplier_invoice_is_excluded_from_supplier_payable_summary(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->update([
                'voided_at' => '2026-03-16 10:00:00',
                'void_reason' => 'Seeded as voided',
            ]);

        $rows = app(GetSupplierPayableSummaryHandler::class)
            ->handle(null, null, '2026-03-20')
            ->data()['rows'];

        $this->assertCount(0, $rows);
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
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Void Invoice',
            'email' => 'admin-void-invoice-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
