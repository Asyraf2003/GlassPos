<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use Illuminate\Support\Facades\DB;

trait SeedsProcurementReceivePaymentPrecisionMatrixFixture
{
    private function seedBaseProcurementState(): void
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
            'jatuh_tempo' => '2026-03-20',
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
            'qty_pcs' => 10,
            'line_total_rupiah' => 100000,
            'unit_cost_rupiah' => 10000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 0,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 0,
            'inventory_value_rupiah' => 0,
        ]);
    }

    private function recordReceipt(string $date, int $qty): void
    {
        $this->postJson('/procurement/supplier-invoices/invoice-1/receive', [
            'tanggal_terima' => $date,
            'lines' => [[
                'supplier_invoice_line_id' => 'invoice-line-1',
                'qty_diterima' => $qty,
            ]],
        ])->assertOk();
    }

    private function payableRows(): array
    {
        return app(GetSupplierPayableSummaryHandler::class)
            ->handle(null, null, '2026-03-20')
            ->data()['rows'];
    }
}
