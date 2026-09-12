<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EditSupplierInvoicePageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_edit_supplier_invoice_page(): void
    {
        $response = $this->get(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-1',
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_edit_supplier_invoice_page(): void
    {
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_to_index_when_supplier_invoice_for_edit_page_is_missing(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'missing-invoice',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));
        $response->assertSessionHas('error', 'Nota supplier tidak ditemukan.');
    }

    public function test_admin_can_access_edit_supplier_invoice_page_when_invoice_is_still_editable(): void
    {
        $this->seedEditableInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertOk();
        $response->assertSee('Edit Nota Pemasok');
        $response->assertSee('INV-SUP-001');
        $response->assertSee('PT Sumber Makmur');
        $response->assertSee('Rincian Nota');
        $response->assertSee('Tambah Produk');
        $response->assertDontSee('add-procurement-line', false);
        $response->assertDontSee('data-product-search', false);
        $response->assertSee('data-product-selected', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-procurement-product-search'));
        $response->assertDontSee('onclick="window.localStorage', false);
        $response->assertSee('data-product-remove', false);
        $response->assertSee('name="lines[0][product_id]"', false);
        $response->assertSee('value="product-1"', false);
        $response->assertSee('data-selected-product-label>Ban Luar', false);
        $response->assertDontSee('value="Ban Luar', false);
        $response->assertSee('admin-procurement-edit.js');
        $response->assertSee('Simpan Perubahan');
    }

    public function test_admin_is_redirected_to_revise_when_supplier_invoice_is_locked_for_edit(): void
    {
        $this->seedEditableInvoice();

        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'paid_at' => '2026-03-16',
            'amount_rupiah' => 5000,
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.revise', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
    }

    private function seedEditableInvoice(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'grand_total_rupiah' => 20000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-1',
            'supplier_invoice_id' => 'invoice-1',
            'line_no' => 1,
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
            'unit_cost_rupiah' => 10000,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role.'-procurement-edit@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
