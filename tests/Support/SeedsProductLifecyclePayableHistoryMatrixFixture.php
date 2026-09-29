<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Reporting\UseCases\GetSupplierPayableSummaryHandler;
use Illuminate\Support\Facades\DB;

trait SeedsProductLifecyclePayableHistoryMatrixFixture
{
    private function seedProduct(string $id, string $kode, string $nama): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kode,
            'nama_barang' => $nama,
            'nama_barang_normalized' => strtolower($nama),
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
            'reorder_point_qty' => 2,
            'critical_threshold_qty' => 1,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function seedSupplier(string $id, string $name): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $name,
            'nama_pt_pengirim_normalized' => strtolower($name),
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function seedInvoice(string $id, string $supplierId, string $nomor, int $grand): void
    {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'supplier_nama_pt_pengirim_snapshot' => (string) DB::table('suppliers')->where('id', $supplierId)->value('nama_pt_pengirim'),
            'nomor_faktur' => $nomor,
            'nomor_faktur_normalized' => strtolower($nomor),
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-20',
            'jatuh_tempo' => '2026-03-21',
            'grand_total_rupiah' => $grand,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 0,
        ]);
    }

    private function seedLine(string $id, string $invoiceId, string $productId, string $kode, string $nama, int $qty, int $lineTotal, int $unitCost, int $lineNo = 1): void
    {
        DB::table('supplier_invoice_lines')->insert([
            'id' => $id,
            'supplier_invoice_id' => $invoiceId,
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => $lineNo,
            'product_id' => $productId,
            'product_kode_barang_snapshot' => $kode,
            'product_nama_barang_snapshot' => $nama,
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => $qty,
            'line_total_rupiah' => $lineTotal,
            'unit_cost_rupiah' => $unitCost,
        ]);
    }

    private function seedPayment(string $id, string $invoiceId, int $amount): void
    {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $invoiceId,
            'amount_rupiah' => $amount,
            'paid_at' => '2026-03-20',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);
    }

    private function seedInventoryState(string $productId, int $qtyOnHand, int $avgCost, int $inventoryValue): void
    {
        DB::table('product_inventory')->insert([
            'product_id' => $productId,
            'qty_on_hand' => $qtyOnHand,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => $productId,
            'avg_cost_rupiah' => $avgCost,
            'inventory_value_rupiah' => $inventoryValue,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-' . $productId,
            'product_id' => $productId,
            'movement_type' => 'stock_in',
            'source_type' => 'seed_fixture',
            'source_id' => 'seed-' . $productId,
            'tanggal_mutasi' => '2026-03-20',
            'qty_delta' => $qtyOnHand,
            'unit_cost_rupiah' => $avgCost,
            'total_cost_rupiah' => $inventoryValue,
        ]);
    }

    private function summary(): array
    {
        return app(GetSupplierPayableSummaryHandler::class)
            ->handle(null, null, '2026-03-20')
            ->data()['rows'];
    }
}
