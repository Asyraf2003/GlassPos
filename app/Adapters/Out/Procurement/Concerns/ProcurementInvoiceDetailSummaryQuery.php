<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Support\Facades\DB;

trait ProcurementInvoiceDetailSummaryQuery
{
    use ProcurementInvoicePolicySqlFragments;

    private function getSummaryRow(string $supplierInvoiceId): ?object
    {
        $paymentTotalsSubquery = DB::table('supplier_payments')
            ->leftJoin(
                'supplier_payment_reversals',
                'supplier_payment_reversals.supplier_payment_id',
                '=',
                'supplier_payments.id'
            )
            ->whereNull('supplier_payment_reversals.id')
            ->selectRaw('supplier_invoice_id, COALESCE(SUM(amount_rupiah), 0) as total_paid_rupiah')
            ->groupBy('supplier_invoice_id');

        $receiptCountSubquery = DB::table('supplier_receipts')
            ->selectRaw('supplier_invoice_id, COUNT(*) as receipt_count, MAX(tanggal_terima) as latest_receipt_date')
            ->groupBy('supplier_invoice_id');

        $receivedQtySubquery = DB::table('supplier_receipts')
            ->join('supplier_receipt_lines', 'supplier_receipt_lines.supplier_receipt_id', '=', 'supplier_receipts.id')
            ->selectRaw('supplier_receipts.supplier_invoice_id, COALESCE(SUM(supplier_receipt_lines.qty_diterima), 0) as total_received_qty')
            ->groupBy('supplier_receipts.supplier_invoice_id');

        return DB::table('supplier_invoices')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_invoices.supplier_id')
            ->leftJoinSub($paymentTotalsSubquery, 'payment_totals', function ($join): void {
                $join->on('payment_totals.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->leftJoinSub($receiptCountSubquery, 'receipt_counts', function ($join): void {
                $join->on('receipt_counts.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->leftJoinSub($receivedQtySubquery, 'received_qty_totals', function ($join): void {
                $join->on('received_qty_totals.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->where('supplier_invoices.id', $supplierInvoiceId)
            ->first([
                'supplier_invoices.id as supplier_invoice_id',
                'supplier_invoices.nomor_faktur',
                'supplier_invoices.supplier_id',
                'suppliers.nama_pt_pengirim as supplier_nama_pt_pengirim_current',
                'supplier_invoices.supplier_nama_pt_pengirim_snapshot as supplier_nama_pt_pengirim_snapshot',
                'supplier_invoices.tanggal_pengiriman as shipment_date',
                'supplier_invoices.jatuh_tempo as due_date',
                'supplier_invoices.subtotal_before_tax_rupiah',
                'supplier_invoices.tax_input',
                'supplier_invoices.tax_mode',
                'supplier_invoices.tax_rate_basis_points',
                'supplier_invoices.tax_amount_rupiah',
                'supplier_invoices.grand_total_rupiah',
                'supplier_invoices.last_revision_no',
                'supplier_invoices.voided_at',
                'supplier_invoices.void_reason',
                DB::raw('COALESCE(payment_totals.total_paid_rupiah, 0) as total_paid_rupiah'),
                DB::raw('supplier_invoices.grand_total_rupiah - COALESCE(payment_totals.total_paid_rupiah, 0) as outstanding_rupiah'),
                DB::raw('COALESCE(receipt_counts.receipt_count, 0) as receipt_count'),
                DB::raw('receipt_counts.latest_receipt_date as latest_receipt_date'),
                DB::raw('COALESCE(received_qty_totals.total_received_qty, 0) as total_received_qty'),
                $this->policyStateSelect(),
                $this->allowedActionsSelect(),
                $this->lockReasonsSelect(),
            ]);
    }
}
