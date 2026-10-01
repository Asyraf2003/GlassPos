<?php

declare(strict_types=1);

use App\Adapters\Out\Procurement\SupplierInvoiceListProjectionReceiptSubqueries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $totals = (new SupplierInvoiceListProjectionReceiptSubqueries)->receivedQtyTotals();
        DB::table('supplier_invoice_list_projection as projection')
            ->leftJoinSub($totals, 'quantities', 'quantities.supplier_invoice_id', '=', 'projection.supplier_invoice_id')
            ->select('projection.supplier_invoice_id')
            ->selectRaw('COALESCE(quantities.total_received_qty, 0) as total_received_qty')
            ->orderBy('projection.supplier_invoice_id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('supplier_invoice_list_projection')
                        ->where('supplier_invoice_id', $row->supplier_invoice_id)
                        ->update(['total_received_qty' => (int) $row->total_received_qty]);
                }
            });
    }

    public function down(): void
    {
        // Derived current state remains accurate; never restore stale quantities.
    }
};
