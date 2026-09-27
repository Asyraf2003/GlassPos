<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SupplierPayableReportingQueryFactory
{
    public function invoiceBalances(?string $fromShipmentDate = null, ?string $toShipmentDate = null): Builder
    {
        $this->assertPeriod($fromShipmentDate, $toShipmentDate);

        return DB::table('supplier_invoices')
            ->leftJoinSub($this->paymentTotalsSubquery(), 'payment_totals', function ($join): void {
                $join->on('payment_totals.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->whereNull('supplier_invoices.voided_at')
            ->when($fromShipmentDate !== null, fn (Builder $query): Builder => $query
                ->whereBetween('supplier_invoices.tanggal_pengiriman', [$fromShipmentDate, $toShipmentDate]));
    }

    private function assertPeriod(?string $from, ?string $to): void
    {
        if (($from === null) !== ($to === null)) {
            throw new \InvalidArgumentException('Supplier payable period requires both bounds or neither.');
        }
    }

    public function paymentTotalsSubquery(): Builder
    {
        return DB::table('supplier_payments')
            ->leftJoin(
                'supplier_payment_reversals',
                'supplier_payment_reversals.supplier_payment_id',
                '=',
                'supplier_payments.id'
            )
            ->whereNull('supplier_payment_reversals.id')
            ->selectRaw('supplier_invoice_id, COALESCE(SUM(amount_rupiah), 0) as total_paid_rupiah')
            ->groupBy('supplier_invoice_id');
    }

    public function receiptCountSubquery(): Builder
    {
        return DB::table('supplier_receipts')
            ->selectRaw('supplier_invoice_id, COUNT(*) as receipt_count')
            ->groupBy('supplier_invoice_id');
    }

    public function receivedQtySubquery(): Builder
    {
        return DB::table('supplier_receipts')
            ->join('supplier_receipt_lines', 'supplier_receipt_lines.supplier_receipt_id', '=', 'supplier_receipts.id')
            ->selectRaw('supplier_receipts.supplier_invoice_id, COALESCE(SUM(supplier_receipt_lines.qty_diterima), 0) as total_received_qty')
            ->groupBy('supplier_receipts.supplier_invoice_id');
    }

    public function filteredInvoicesSubquery(?string $fromShipmentDate, ?string $toShipmentDate): Builder
    {
        $this->assertPeriod($fromShipmentDate, $toShipmentDate);

        return DB::table('supplier_invoices')
            ->select('id', 'grand_total_rupiah')
            ->whereNull('voided_at')
            ->when($fromShipmentDate !== null, fn (Builder $query): Builder => $query
                ->whereBetween('tanggal_pengiriman', [$fromShipmentDate, $toShipmentDate]));
    }
}
