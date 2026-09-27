<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Ports\Out\Reporting\SupplierPayableReportingSourceReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPayableReportingSourceReaderAdapter implements SupplierPayableReportingSourceReaderPort
{
    public function __construct(
        private readonly SupplierPayableReportingQueryFactory $queries,
    ) {
    }

    public function getSupplierPayableSummaryRows(?string $fromShipmentDate, ?string $toShipmentDate): array
    {
        return $this->queries->invoiceBalances($fromShipmentDate, $toShipmentDate)
            ->leftJoinSub($this->queries->receiptCountSubquery(), 'receipt_counts', function ($join): void {
                $join->on('receipt_counts.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->leftJoinSub($this->queries->receivedQtySubquery(), 'received_qty_totals', function ($join): void {
                $join->on('received_qty_totals.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->orderBy('supplier_invoices.tanggal_pengiriman')
            ->orderBy('supplier_invoices.id')
            ->get([
                'supplier_invoices.id as supplier_invoice_id',
                'supplier_invoices.nomor_faktur',
                'supplier_invoices.supplier_id',
                'supplier_invoices.supplier_nama_pt_pengirim_snapshot as supplier_name',
                'supplier_invoices.tanggal_pengiriman as shipment_date',
                'supplier_invoices.jatuh_tempo as due_date',
                'supplier_invoices.grand_total_rupiah',
                DB::raw('COALESCE(payment_totals.total_paid_rupiah, 0) as total_paid_rupiah'),
                DB::raw('COALESCE(receipt_counts.receipt_count, 0) as receipt_count'),
                DB::raw('COALESCE(received_qty_totals.total_received_qty, 0) as total_received_qty'),
            ])
            ->map(static fn (object $row): array => [
                'supplier_invoice_id' => (string) $row->supplier_invoice_id,
                'nomor_faktur' => $row->nomor_faktur !== null ? (string) $row->nomor_faktur : (string) $row->supplier_invoice_id,
                'supplier_id' => (string) $row->supplier_id,
                'supplier_name' => $row->supplier_name !== null ? (string) $row->supplier_name : (string) $row->supplier_id,
                'shipment_date' => (string) $row->shipment_date,
                'due_date' => (string) $row->due_date,
                'grand_total_rupiah' => (int) $row->grand_total_rupiah,
                'total_paid_rupiah' => (int) $row->total_paid_rupiah,
                'receipt_count' => (int) $row->receipt_count,
                'total_received_qty' => (int) $row->total_received_qty,
            ])
            ->all();
    }

    public function getSupplierPayableSummaryReconciliation(?string $fromShipmentDate, ?string $toShipmentDate): array
    {
        $filteredInvoicesSubquery = $this->queries->filteredInvoicesSubquery($fromShipmentDate, $toShipmentDate);

        $invoiceTotals = DB::query()
            ->fromSub($filteredInvoicesSubquery, 'filtered_invoices')
            ->selectRaw('COUNT(*) as total_rows, COALESCE(SUM(grand_total_rupiah), 0) as grand_total_rupiah')
            ->first();

        $paymentTotals = DB::table('supplier_payments')
            ->leftJoin(
                'supplier_payment_reversals',
                'supplier_payment_reversals.supplier_payment_id',
                '=',
                'supplier_payments.id'
            )
            ->whereNull('supplier_payment_reversals.id')
            ->joinSub($filteredInvoicesSubquery, 'filtered_invoices', function ($join): void {
                $join->on('filtered_invoices.id', '=', 'supplier_payments.supplier_invoice_id');
            })
            ->selectRaw('COALESCE(SUM(supplier_payments.amount_rupiah), 0) as total_paid_rupiah')
            ->first();

        $grandTotal = (int) ($invoiceTotals->grand_total_rupiah ?? 0);
        $totalPaid = (int) ($paymentTotals->total_paid_rupiah ?? 0);

        return [
            'total_rows' => (int) ($invoiceTotals->total_rows ?? 0),
            'grand_total_rupiah' => $grandTotal,
            'total_paid_rupiah' => $totalPaid,
            'outstanding_rupiah' => $grandTotal - $totalPaid,
        ];
    }
}
