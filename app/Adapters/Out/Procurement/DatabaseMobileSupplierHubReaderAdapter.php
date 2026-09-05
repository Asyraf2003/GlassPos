<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\MobileSupplierHubReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseMobileSupplierHubReaderAdapter implements MobileSupplierHubReaderPort
{
    public function outstandingInvoices(int $limit = 100): array
    {
        $paymentTotals = DB::table('supplier_payments')
            ->leftJoin(
                'supplier_payment_reversals',
                'supplier_payment_reversals.supplier_payment_id',
                '=',
                'supplier_payments.id',
            )
            ->whereNull('supplier_payment_reversals.id')
            ->selectRaw('supplier_invoice_id, COALESCE(SUM(amount_rupiah), 0) as total_paid_rupiah')
            ->groupBy('supplier_invoice_id');

        return DB::table('supplier_invoices')
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join): void {
                $join->on('payment_totals.supplier_invoice_id', '=', 'supplier_invoices.id');
            })
            ->whereNull('supplier_invoices.voided_at')
            ->whereRaw('(supplier_invoices.grand_total_rupiah - COALESCE(payment_totals.total_paid_rupiah, 0)) > 0')
            ->orderBy('supplier_invoices.jatuh_tempo')
            ->orderBy('supplier_invoices.id')
            ->limit($this->limit($limit))
            ->get([
                'supplier_invoices.id as supplier_invoice_id',
                'supplier_invoices.nomor_faktur',
                'supplier_invoices.supplier_nama_pt_pengirim_snapshot as supplier_name',
                'supplier_invoices.jatuh_tempo as due_date',
                DB::raw('(supplier_invoices.grand_total_rupiah - COALESCE(payment_totals.total_paid_rupiah, 0)) as outstanding_rupiah'),
            ])
            ->map(static fn (object $row): array => [
                'supplier_invoice_id' => (string) $row->supplier_invoice_id,
                'invoice_no' => (string) ($row->nomor_faktur ?? $row->supplier_invoice_id),
                'supplier_name' => (string) ($row->supplier_name ?? '-'),
                'due_date' => (string) ($row->due_date ?? ''),
                'outstanding_rupiah' => (int) $row->outstanding_rupiah,
            ])
            ->all();
    }

    public function recentPaymentProofs(int $limit = 100): array
    {
        return DB::table('supplier_payment_proof_attachments as proof')
            ->join('supplier_payments as payment', 'payment.id', '=', 'proof.supplier_payment_id')
            ->join('supplier_invoices as invoice', 'invoice.id', '=', 'payment.supplier_invoice_id')
            ->leftJoin('supplier_payment_reversals as reversal', 'reversal.supplier_payment_id', '=', 'payment.id')
            ->whereNull('reversal.id')
            ->orderByDesc('payment.paid_at')
            ->orderByDesc('proof.uploaded_at')
            ->orderByDesc('proof.id')
            ->limit($this->limit($limit))
            ->get([
                'proof.id as attachment_id',
                'proof.original_filename',
                'payment.id as supplier_payment_id',
                'payment.amount_rupiah',
                'payment.paid_at',
                'invoice.nomor_faktur',
                'invoice.supplier_nama_pt_pengirim_snapshot as supplier_name',
            ])
            ->map(static fn (object $row): array => [
                'attachment_id' => (string) $row->attachment_id,
                'original_filename' => (string) $row->original_filename,
                'supplier_payment_id' => (string) $row->supplier_payment_id,
                'amount_rupiah' => (int) $row->amount_rupiah,
                'paid_at' => (string) $row->paid_at,
                'invoice_no' => (string) ($row->nomor_faktur ?? $row->supplier_payment_id),
                'supplier_name' => (string) ($row->supplier_name ?? '-'),
            ])
            ->all();
    }

    private function limit(int $limit): int
    {
        return max(1, min($limit, 100));
    }
}
