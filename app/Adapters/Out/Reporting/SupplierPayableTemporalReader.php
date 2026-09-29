<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use Illuminate\Support\Facades\DB;

final class SupplierPayableTemporalReader
{
    public function __construct(private readonly SupplierInvoiceTemporalState $states) {}

    public function rows(string $from, string $to): array
    {
        $openingCutoff = (new \DateTimeImmutable($from))->modify('-1 second')->format('Y-m-d H:i:s');
        $closingCutoff = $to.' 23:59:59';
        $versions = DB::table('supplier_invoice_versions')->orderBy('revision_no')->get()->groupBy('supplier_invoice_id');
        $payments = DB::table('supplier_payments as p')->leftJoin('supplier_payment_reversals as r', 'r.supplier_payment_id', '=', 'p.id')
            ->get(['p.supplier_invoice_id', 'p.amount_rupiah', 'p.paid_at', 'r.created_at as reversed_at'])->groupBy('supplier_invoice_id');
        $receipts = DB::table('supplier_receipts')->where('tanggal_terima', '<=', $to)->get()->groupBy('supplier_invoice_id');
        $qty = DB::table('supplier_receipts as r')->join('supplier_receipt_lines as l', 'l.supplier_receipt_id', '=', 'r.id')
            ->where('r.tanggal_terima', '<=', $to)->selectRaw('r.supplier_invoice_id, SUM(l.qty_diterima) as qty')->groupBy('r.supplier_invoice_id')->pluck('qty', 'supplier_invoice_id');
        $rows = [];
        foreach (DB::table('supplier_invoices')->orderBy('tanggal_pengiriman')->orderBy('id')->get() as $invoice) {
            $history = ($versions[$invoice->id] ?? collect())->all();
            $opening = $this->states->at($invoice, $history, $openingCutoff);
            $closing = $this->states->at($invoice, $history, $closingCutoff);
            if ($closing === null || (($opening['voided'] ?? false) && ($closing['voided'] ?? false))) {
                continue;
            }
            $openingPrincipal = (int) ($opening['grand_total_rupiah'] ?? 0);
            $closingPrincipal = (int) $closing['grand_total_rupiah'];
            $initialDate = $history === [] ? $invoice->tanggal_pengiriman : substr($history[0]->changed_at, 0, 10);
            $new = $initialDate >= $from && $initialDate <= $to
                ? (int) ($history === [] ? $invoice->grand_total_rupiah : json_decode($history[0]->snapshot_json, true, flags: JSON_THROW_ON_ERROR)['grand_total_rupiah']) : 0;
            $paidOpening = $paidClosing = $periodPaid = $periodReversed = 0;
            foreach ($payments[$invoice->id] ?? [] as $payment) {
                $amount = (int) $payment->amount_rupiah;
                $date = substr($payment->paid_at, 0, 10);
                $reversal = $payment->reversed_at;
                if ($date < $from && ($reversal === null || $reversal > $openingCutoff)) {
                    $paidOpening += $amount;
                }
                if ($date <= $to && ($reversal === null || $reversal > $closingCutoff)) {
                    $paidClosing += $amount;
                }
                if ($date >= $from && $date <= $to) {
                    $periodPaid += $amount;
                }
                if ($date <= $to && $reversal !== null && $reversal >= $from.' 00:00:00' && $reversal <= $closingCutoff) {
                    $periodReversed += $amount;
                }
            }
            $rows[] = [
                'supplier_invoice_id' => (string) $invoice->id,
                'nomor_faktur' => (string) ($closing['nomor_faktur'] ?? $invoice->id),
                'supplier_id' => (string) $closing['supplier']['id'],
                'supplier_name' => (string) $closing['supplier']['nama_pt_pengirim_snapshot'],
                'shipment_date' => (string) $closing['tanggal_pengiriman'], 'due_date' => (string) $closing['jatuh_tempo'],
                'grand_total_rupiah' => $closingPrincipal, 'total_paid_rupiah' => $paidClosing,
                'receipt_count' => count($receipts[$invoice->id] ?? []), 'total_received_qty' => (int) ($qty[$invoice->id] ?? 0),
                'opening_outstanding_rupiah' => $openingPrincipal - $paidOpening,
                'new_invoices_rupiah' => $new,
                'adjustments_in_period_rupiah' => $closingPrincipal - $openingPrincipal - $new,
                'payments_in_period_rupiah' => $periodPaid, 'reversals_in_period_rupiah' => $periodReversed,
                'voided_as_of' => (bool) ($closing['voided'] ?? false),
                'history_basis' => $history === [] ? 'legacy_shipment_date' : 'recorded_versions',
            ];
        }

        return $rows;
    }
}
