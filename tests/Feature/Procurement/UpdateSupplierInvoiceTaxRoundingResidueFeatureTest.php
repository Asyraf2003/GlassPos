<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateSupplierInvoiceTaxRoundingResidueFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_tax_rounding_residue_is_persisted_on_update(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->seedEditableInvoice();

        $response = $this->put(route('admin.procurement.supplier-invoices.update', [
            'supplierInvoiceId' => 'invoice-rounding-1',
        ]), [
            'expected_revision_no' => 1,
            'change_reason' => 'Konfirmasi residue pajak supplier.',
            'nomor_faktur' => 'INV-SUP-ROUNDING-UPDATED',
            'nama_pt_pengirim' => 'PT Supplier Rounding',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => '1',
            'tax_rounding_residue_confirmed' => true,
            'lines' => [[
                'previous_line_id' => 'invoice-line-rounding-1',
                'line_no' => 1,
                'product_id' => 'product-rounding-1',
                'qty_pcs' => 3,
                'line_total_rupiah' => 300,
            ]],
        ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-rounding-1',
        ]));

        $invoice = DB::table('supplier_invoices')->where('id', 'invoice-rounding-1')->first();

        $this->assertSame(300, (int) $invoice->subtotal_before_tax_rupiah);
        $this->assertSame(1, (int) $invoice->tax_amount_rupiah);
        $this->assertSame(301, (int) $invoice->grand_total_rupiah);
        $this->assertSame(2, (int) $invoice->last_revision_no);

        $line = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', 'invoice-rounding-1')
            ->where('is_current', true)
            ->first();

        $this->assertSame(301, (int) $line->line_total_rupiah);
        $this->assertSame(100, (int) $line->unit_cost_rupiah);
        $this->assertSame(1, (int) $line->rounding_residue_rupiah);

        $version = DB::table('supplier_invoice_versions')
            ->where('supplier_invoice_id', 'invoice-rounding-1')
            ->where('revision_no', 2)
            ->first();

        $snapshot = json_decode((string) $version->snapshot_json, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $snapshot['lines'][0]['rounding_residue_rupiah']);
    }


    public function test_unconfirmed_tax_rounding_residue_is_rejected_on_update_without_500(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->seedEditableInvoice();

        $response = $this->from(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-rounding-1',
        ]))->put(route('admin.procurement.supplier-invoices.update', [
            'supplierInvoiceId' => 'invoice-rounding-1',
        ]), [
            'expected_revision_no' => 1,
            'change_reason' => 'Belum konfirmasi residue pajak supplier.',
            'nomor_faktur' => 'INV-SUP-ROUNDING-UNCONFIRMED',
            'nama_pt_pengirim' => 'PT Supplier Rounding',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => '1',
            'lines' => [[
                'previous_line_id' => 'invoice-line-rounding-1',
                'line_no' => 1,
                'product_id' => 'product-rounding-1',
                'qty_pcs' => 3,
                'line_total_rupiah' => 300,
            ]],
        ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-rounding-1',
        ]));
        $response->assertSessionHasErrors(['tax_input']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-rounding-1',
            'nomor_faktur' => 'INV-SUP-ROUNDING',
            'grand_total_rupiah' => 300,
            'last_revision_no' => 1,
        ]);

        $this->assertDatabaseMissing('supplier_invoice_versions', [
            'supplier_invoice_id' => 'invoice-rounding-1',
            'revision_no' => 2,
        ]);
    }

    private function seedEditableInvoice(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-rounding-1',
            'nama_pt_pengirim' => 'PT Supplier Rounding',
            'nama_pt_pengirim_normalized' => 'pt supplier rounding',
        ]);

        DB::table('products')->insert([
            'id' => 'product-rounding-1',
            'kode_barang' => 'ROUND-001',
            'nama_barang' => 'Barang Rounding',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 15000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-rounding-1',
            'supplier_id' => 'supplier-rounding-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Rounding',
            'nomor_faktur' => 'INV-SUP-ROUNDING',
            'nomor_faktur_normalized' => 'inv-sup-rounding',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'tanggal_pengiriman' => '2026-03-12',
            'jatuh_tempo' => '2026-04-12',
            'subtotal_before_tax_rupiah' => 300,
            'grand_total_rupiah' => 300,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-rounding-1',
            'supplier_invoice_id' => 'invoice-rounding-1',
            'revision_no' => 1,
            'is_current' => true,
            'line_no' => 1,
            'product_id' => 'product-rounding-1',
            'product_kode_barang_snapshot' => 'ROUND-001',
            'product_nama_barang_snapshot' => 'Barang Rounding',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 3,
            'line_total_rupiah' => 300,
            'unit_cost_rupiah' => 100,
        ]);
    }
}
