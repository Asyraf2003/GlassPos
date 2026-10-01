<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SupplierInvoiceListProjectionReceiptSubqueries
{
    public function counts(): Builder
    {
        return DB::table('supplier_receipts')
            ->selectRaw('supplier_invoice_id, COUNT(*) as receipt_count')
            ->groupBy('supplier_invoice_id');
    }

    public function receivedQtyTotals(): Builder
    {
        $receipts = DB::table('supplier_receipts')
            ->join('supplier_receipt_lines', 'supplier_receipt_lines.supplier_receipt_id', '=', 'supplier_receipts.id')
            ->selectRaw('supplier_receipts.supplier_invoice_id, supplier_receipt_lines.qty_diterima as qty');

        // Accepted revisions record quantity corrections as deltas. Include all
        // revisions (also superseded lines), leaving receipt/history rows intact.
        $revisions = DB::table('inventory_movements')
            ->join('supplier_invoice_lines', 'supplier_invoice_lines.id', '=', 'inventory_movements.source_id')
            ->where('inventory_movements.source_type', 'supplier_invoice_revision_delta_line')
            ->selectRaw('supplier_invoice_lines.supplier_invoice_id, inventory_movements.qty_delta as qty');

        return DB::query()->fromSub($receipts->unionAll($revisions), 'received_quantities')
            ->selectRaw('supplier_invoice_id, COALESCE(SUM(qty), 0) as total_received_qty')
            ->groupBy('supplier_invoice_id');
    }
}
