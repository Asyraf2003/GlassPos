<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class CreateSupplierInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_create_supplier_invoice_endpoint_auto_receives_by_default_and_updates_inventory_without_auto_recording_payment(): void
    {
        $this->loginAsKasir();

        $this->seedMinimalProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedMinimalProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => ' INV-SUP-2026-0001 ',
            'nama_pt_pengirim' => '  PT Sumber Makmur  ',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'product_kode_barang_snapshot' => 'KB-001',
                    'product_nama_barang_snapshot' => 'Ban Luar',
                    'product_merek_snapshot' => 'Federal',
                    'product_ukuran_snapshot' => 100,
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
                [
                    'line_no' => 2,
                    'product_id' => 'product-2',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 30000,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('suppliers', [
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        $supplier = DB::table('suppliers')
            ->where('nama_pt_pengirim_normalized', 'pt sumber makmur')
            ->first();

        $this->assertNotNull($supplier);

        $this->assertDatabaseHas('supplier_invoices', [
            'supplier_id' => (string) $supplier->id,
            'nomor_faktur' => 'INV-SUP-2026-0001',
            'nomor_faktur_normalized' => 'inv-sup-2026-0001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'tanggal_pengiriman' => '2026-03-12',
            'jatuh_tempo' => '2026-04-12',
            'subtotal_before_tax_rupiah' => 50000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 50000,
            'last_revision_no' => 1,
        ]);

        $invoice = DB::table('supplier_invoices')
            ->where('supplier_id', (string) $supplier->id)
            ->first();

        $this->assertNotNull($invoice);

        $this->assertDatabaseCount('supplier_payments', 0);

        $invoiceLine1 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-1')
            ->where('line_no', 1)
            ->first();

        $invoiceLine2 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-2')
            ->where('line_no', 2)
            ->first();

        $this->assertNotNull($invoiceLine1);
        $this->assertNotNull($invoiceLine2);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => (string) $invoice->id,
            'revision_no' => 1,
            'event_name' => 'supplier_invoice_created',
        ]);

        $auditEvent = DB::table('audit_events')
            ->where('aggregate_type', 'supplier_invoice')
            ->where('aggregate_id', (string) $invoice->id)
            ->where('event_name', 'supplier_invoice_created')
            ->first();

        $this->assertNotNull($auditEvent);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => (string) $auditEvent->id,
            'snapshot_kind' => 'after',
        ]);

        $this->assertDatabaseHas('supplier_receipts', [
            'supplier_invoice_id' => (string) $invoice->id,
            'tanggal_terima' => '2026-03-12',
        ]);

        $receipt = DB::table('supplier_receipts')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->first();

        $this->assertNotNull($receipt);

        $this->assertDatabaseHas('supplier_receipt_lines', [
            'supplier_receipt_id' => (string) $receipt->id,
            'supplier_invoice_line_id' => (string) $invoiceLine1->id,
            'qty_diterima' => 2,
        ]);

        $this->assertDatabaseHas('supplier_receipt_lines', [
            'supplier_receipt_id' => (string) $receipt->id,
            'supplier_invoice_line_id' => (string) $invoiceLine2->id,
            'qty_diterima' => 3,
        ]);

        $receiptLine1 = DB::table('supplier_receipt_lines')
            ->where('supplier_receipt_id', (string) $receipt->id)
            ->where('supplier_invoice_line_id', (string) $invoiceLine1->id)
            ->first();

        $receiptLine2 = DB::table('supplier_receipt_lines')
            ->where('supplier_receipt_id', (string) $receipt->id)
            ->where('supplier_invoice_line_id', (string) $invoiceLine2->id)
            ->first();

        $this->assertNotNull($receiptLine1);
        $this->assertNotNull($receiptLine2);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => (string) $receiptLine1->id,
            'tanggal_mutasi' => '2026-03-12',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-2',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => (string) $receiptLine2->id,
            'tanggal_mutasi' => '2026-03-12',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 2,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-2',
            'qty_on_hand' => 3,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-2',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 30000,
        ]);
    }

    public function test_create_supplier_invoice_endpoint_allocates_tax_to_landed_cost_and_inventory_costing(): void
    {
        $this->loginAsKasir();

        $this->seedMinimalProduct('product-tax-1', 'TAX-001', 'Taxable One', 'Federal', 100, 15000);
        $this->seedMinimalProduct('product-tax-2', 'TAX-002', 'Taxable Two', 'Federal', 90, 17000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-TAX-001',
            'nama_pt_pengirim' => 'PT Supplier Pajak',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => '10%',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-tax-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
                [
                    'line_no' => 2,
                    'product_id' => 'product-tax-2',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 30000,
                ],
            ],
        ]);

        $response->assertOk();

        $invoice = DB::table('supplier_invoices')
            ->where('nomor_faktur_normalized', 'inv-sup-tax-001')
            ->first();

        $this->assertNotNull($invoice);

        $this->assertSame(50000, (int) $invoice->subtotal_before_tax_rupiah);
        $this->assertSame('10%', (string) $invoice->tax_input);
        $this->assertSame('percent', (string) $invoice->tax_mode);
        $this->assertSame(1000, (int) $invoice->tax_rate_basis_points);
        $this->assertSame(5000, (int) $invoice->tax_amount_rupiah);
        $this->assertSame(55000, (int) $invoice->grand_total_rupiah);

        $line1 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-tax-1')
            ->first();

        $line2 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-tax-2')
            ->first();

        $this->assertNotNull($line1);
        $this->assertNotNull($line2);

        $this->assertSame(22000, (int) $line1->line_total_rupiah);
        $this->assertSame(11000, (int) $line1->unit_cost_rupiah);
        $this->assertSame(33000, (int) $line2->line_total_rupiah);
        $this->assertSame(11000, (int) $line2->unit_cost_rupiah);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-tax-1',
            'movement_type' => 'stock_in',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 11000,
            'total_cost_rupiah' => 22000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-tax-2',
            'movement_type' => 'stock_in',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 11000,
            'total_cost_rupiah' => 33000,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-2',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 33000,
        ]);
    }

    public function test_create_supplier_invoice_endpoint_rejects_invalid_supplier_tax_input(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-tax-invalid-1', 'TAX-INV-001', 'Tax Invalid', 'Federal', 100, 15000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-TAX-INVALID-001',
            'nama_pt_pengirim' => 'PT Supplier Pajak Invalid',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => 'abc%',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-tax-invalid-1',
                    'qty_pcs' => 1,
                    'line_total_rupiah' => 10000,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tax_input']);

        $this->assertDatabaseMissing('supplier_invoices', [
            'nomor_faktur_normalized' => 'inv-sup-tax-invalid-001',
        ]);
    }

    public function test_create_supplier_invoice_endpoint_rejects_supplier_tax_allocation_that_breaks_unit_cost_invariant(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-tax-invalid-2', 'TAX-INV-002', 'Tax Invalid Unit Cost', 'Federal', 100, 15000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-TAX-INVALID-002',
            'nama_pt_pengirim' => 'PT Supplier Pajak Invalid',
            'tanggal_pengiriman' => '2026-03-12',
            'tax_input' => '1',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-tax-invalid-2',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 300,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tax_input']);

        $this->assertDatabaseMissing('supplier_invoices', [
            'nomor_faktur_normalized' => 'inv-sup-tax-invalid-002',
        ]);
    }

    public function test_create_supplier_invoice_endpoint_rejects_unknown_product(): void
    {
        $this->loginAsKasir();

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-2026-0002',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'unknown-product',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseCount('audit_event_snapshots', 0);
        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
        $this->assertDatabaseCount('product_inventory_costing', 0);
    }

    public function test_create_supplier_invoice_endpoint_rejects_line_total_that_is_not_evenly_divisible_by_qty(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-2026-0003',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'product_kode_barang_snapshot' => 'KB-001',
                    'product_nama_barang_snapshot' => 'Ban Luar',
                    'product_merek_snapshot' => 'Federal',
                    'product_ukuran_snapshot' => 100,
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 10000,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseCount('audit_event_snapshots', 0);
        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
        $this->assertDatabaseCount('product_inventory_costing', 0);
    }

    public function test_create_supplier_invoice_endpoint_rejects_duplicate_line_no(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedMinimalProduct('product-2', 'KB-002', 'Vario', 'Federal', 90, 17000);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-2026-0004',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
                [
                    'line_no' => 1,
                    'product_id' => 'product-2',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 30000,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lines.1.line_no']);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseCount('audit_event_snapshots', 0);
    }

    public function test_create_supplier_invoice_endpoint_can_disable_auto_receive_and_reuse_existing_supplier_without_auto_recording_payment(): void
    {
        $this->loginAsKasir();
        $this->seedMinimalProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);
        $this->seedMinimalSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nomor_faktur' => 'INV-SUP-2026-0005',
            'nama_pt_pengirim' => '  pt   sumber    makmur ',
            'tanggal_pengiriman' => '2026-01-30',
            'auto_receive' => false,
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'product_kode_barang_snapshot' => 'KB-001',
                    'product_nama_barang_snapshot' => 'Ban Luar',
                    'product_merek_snapshot' => 'Federal',
                    'product_ukuran_snapshot' => 100,
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('suppliers', 1);

        $this->assertDatabaseHas('suppliers', [
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        $this->assertDatabaseHas('supplier_invoices', [
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
            'nomor_faktur' => 'INV-SUP-2026-0005',
            'nomor_faktur_normalized' => 'inv-sup-2026-0005',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'tanggal_pengiriman' => '2026-01-30',
            'jatuh_tempo' => '2026-02-28',
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 1,
        ]);

        $invoice = DB::table('supplier_invoices')
            ->where('supplier_id', 'supplier-1')
            ->first();

        $this->assertNotNull($invoice);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'supplier_invoice_id' => (string) $invoice->id,
            'line_no' => 1,
            'product_id' => 'product-1',
        ]);

        $this->assertDatabaseHas('supplier_invoice_versions', [
            'supplier_invoice_id' => (string) $invoice->id,
            'revision_no' => 1,
            'event_name' => 'supplier_invoice_created',
        ]);

        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
        $this->assertDatabaseCount('product_inventory_costing', 0);
    }


    public function test_create_supplier_invoice_endpoint_rejects_mixed_header_and_line_tax_input(): void
    {
        $this->seedProduct('product-tax-mixed-1', 'TAX-MIX-001', 'Ban Tax Mixed', 'Federal', 90, 35000);

        $payload = $this->validPayload([
            'tax_input' => '10%',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-tax-mixed-1',
                    'qty_pcs' => 1,
                    'line_total_rupiah' => 100000,
                    'tax_input' => '11%',
                ],
            ],
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->postJson(route('admin.procurement.supplier-invoices.store'), $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['tax_input']);
    }

    public function test_create_supplier_invoice_endpoint_accepts_line_tax_input(): void
    {
        $this->seedMinimalProduct('product-1', 'KB-001', 'Supra', 'Federal', 100, 15000);

        $admin = $this->loginAsAuthorizedAdmin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.procurement.supplier-invoices.store'), [
            'nomor_faktur' => 'INV-SUP-LINE-TAX-001',
            'nama_pt_pengirim' => 'PT Supplier Line Tax',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'qty_pcs' => 1,
                    'line_total_rupiah' => 100000,
                    'tax_input' => ' 11% ',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.index'));

        $invoice = DB::table('supplier_invoices')
            ->where('nomor_faktur_normalized', 'inv-sup-line-tax-001')
            ->first();

        $line = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $invoice->id)
            ->where('product_id', 'product-1')
            ->first();

        $this->assertSame(111000, (int) $line->line_total_rupiah);
        $this->assertSame(111000, (int) $line->unit_cost_rupiah);
        $this->assertSame(100000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame('11%', (string) $line->tax_input);
        $this->assertSame('percent', (string) $line->tax_mode);
        $this->assertSame(1100, (int) $line->tax_rate_basis_points);
        $this->assertSame(11000, (int) $line->tax_amount_rupiah);
    }

}
