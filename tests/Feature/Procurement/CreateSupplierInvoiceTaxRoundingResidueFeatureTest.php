<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class CreateSupplierInvoiceTaxRoundingResidueFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_confirmed_tax_rounding_residue_is_persisted_explicitly(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-tax-rounding-1', 'TR-001', 'Tax Rounding', 'Federal', 90, 15000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-TAX-ROUNDING-001',
            'nama_pt_pengirim' => 'PT Supplier Rounding',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => '1',
            'tax_rounding_residue_confirmed' => true,
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-tax-rounding-1',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 300,
                ],
            ],
        ]);

        $response->assertOk();

        $invoice = DB::table('supplier_invoices')
            ->where('nomor_faktur_normalized', 'inv-sup-tax-rounding-001')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertSame(300, (int) $invoice->subtotal_before_tax_rupiah);
        $this->assertSame(1, (int) $invoice->tax_amount_rupiah);
        $this->assertSame(301, (int) $invoice->grand_total_rupiah);

        $line = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-tax-rounding-1')
            ->where('is_current', true)
            ->first();

        $this->assertNotNull($line);
        $this->assertSame(301, (int) $line->line_total_rupiah);
        $this->assertSame(100, (int) $line->unit_cost_rupiah);
        $this->assertSame(1, (int) $line->rounding_residue_rupiah);

        $version = DB::table('supplier_invoice_versions')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('revision_no', 1)
            ->first();

        $this->assertNotNull($version);
        $snapshot = json_decode((string) $version->snapshot_json, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $snapshot['lines'][0]['rounding_residue_rupiah']);
    }
}
