<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierInvoiceTaxUiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_supplier_tax_input(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.create'));

        $response->assertOk();
        $response->assertSee('Pajak Supplier');
        $response->assertSee('name="tax_input"', false);
        $response->assertSee('data-tax-header-input', false);
        $response->assertSeeInOrder([
            'for="tax_input"',
            'name="tax_input"',
            'data-tax-header-input',
        ], false);
        $response->assertSeeInOrder([
            'for="tax_input"',
            'name="tax_input"',
            'data-tax-header-input',
        ], false);
        $response->assertSee('Contoh: 11% atau 15000');
        $response->assertSee('Pajak Rincian');
        $response->assertSee('name="lines[0][tax_input]"', false);
        $response->assertSee('data-tax-line-input', false);
        $response->assertSee('data-tax-line-group', false);
    }

    public function test_edit_page_renders_supplier_tax_input_with_existing_value(): void
    {
        $this->seedInvoiceWithTax();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-ui-1',
            ]));

        $response->assertOk();
        $response->assertSee('Pajak Supplier');
        $response->assertSee('name="tax_input"', false);
        $response->assertSee('data-tax-header-input', false);
        $response->assertSee('value="10%"', false);
        $response->assertSee('Pajak Rincian');
        $response->assertSee('name="lines[0][tax_input]"', false);
    }

    public function test_show_page_renders_supplier_tax_summary(): void
    {
        $this->seedInvoiceWithTax();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', [
                'supplierInvoiceId' => 'invoice-tax-ui-1',
            ]));

        $response->assertOk();
        $response->assertSee('Subtotal Sebelum Pajak');
        $response->assertSee('Pajak Supplier');
        $response->assertSee('Input: 10%');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Rp 5.000');
        $response->assertSee('Rp 55.000');
    }

    public function test_edit_page_renders_line_tax_input_with_existing_value(): void
    {
        $this->seedInvoiceWithLineTax();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-line-tax-ui-1',
            ]));

        $response->assertOk();
        $response->assertSee('Pajak Rincian');
        $response->assertSee('name="lines[0][tax_input]"', false);
        $response->assertSee('data-tax-line-input', false);
        $response->assertSee('data-tax-line-group', false);
        $response->assertSee('value="11%"', false);
    }

    public function test_show_page_renders_line_tax_details(): void
    {
        $this->seedInvoiceWithLineTax();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', [
                'supplierInvoiceId' => 'invoice-line-tax-ui-1',
            ]));

        $response->assertOk();
        $response->assertSee('Subtotal Sebelum Pajak');
        $response->assertSee('Pajak Rincian');
        $response->assertSee('Input: 11%');
        $response->assertSee('Rp 100.000');
        $response->assertSee('Rp 11.000');
        $response->assertSee('Rp 111.000');
    }

    private function seedInvoiceWithTax(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-tax-ui-1',
            'nama_pt_pengirim' => 'PT Supplier Tax UI',
            'nama_pt_pengirim_normalized' => 'pt supplier tax ui',
        ]);

        DB::table('products')->insert([
            'id' => 'product-tax-ui-1',
            'kode_barang' => 'TAX-UI-001',
            'nama_barang' => 'Barang Tax UI',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-tax-ui-1',
            'supplier_id' => 'supplier-tax-ui-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Tax UI',
            'nomor_faktur' => 'INV-TAX-UI-1',
            'nomor_faktur_normalized' => 'inv-tax-ui-1',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-12',
            'jatuh_tempo' => '2026-04-12',
            'subtotal_before_tax_rupiah' => 50000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 5000,
            'grand_total_rupiah' => 55000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-tax-ui-line-1',
            'supplier_invoice_id' => 'invoice-tax-ui-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-tax-ui-1',
            'product_kode_barang_snapshot' => 'TAX-UI-001',
            'product_nama_barang_snapshot' => 'Barang Tax UI',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 5,
            'line_total_rupiah' => 55000,
            'unit_cost_rupiah' => 11000,
        ]);
    }


    private function seedInvoiceWithLineTax(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-line-tax-ui-1',
            'nama_pt_pengirim' => 'PT Supplier Line Tax UI',
            'nama_pt_pengirim_normalized' => 'pt supplier line tax ui',
        ]);

        DB::table('products')->insert([
            'id' => 'product-line-tax-ui-1',
            'kode_barang' => 'LINE-TAX-UI-001',
            'nama_barang' => 'Barang Line Tax UI',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-line-tax-ui-1',
            'supplier_id' => 'supplier-line-tax-ui-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Line Tax UI',
            'nomor_faktur' => 'INV-LINE-TAX-UI-1',
            'nomor_faktur_normalized' => 'inv-line-tax-ui-1',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-12',
            'jatuh_tempo' => '2026-04-12',
            'subtotal_before_tax_rupiah' => 100000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 111000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-tax-ui-line-1',
            'supplier_invoice_id' => 'invoice-line-tax-ui-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-line-tax-ui-1',
            'product_kode_barang_snapshot' => 'LINE-TAX-UI-001',
            'product_nama_barang_snapshot' => 'Barang Line Tax UI',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 1,
            'line_total_rupiah' => 111000,
            'unit_cost_rupiah' => 111000,
            'line_subtotal_before_tax_rupiah' => 100000,
            'tax_input' => '11%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1100,
            'tax_amount_rupiah' => 11000,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-supplier-tax-ui@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
