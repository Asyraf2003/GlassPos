<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Application\Procurement\DTO\ProcurementInvoiceTableQuery;
use Illuminate\Database\Query\Builder;

final class ProcurementInvoiceProjectionTableSorting
{
    public function apply(Builder $query, ProcurementInvoiceTableQuery $filters): Builder
    {
        $sortDir = $filters->sortDir() === 'asc' ? 'asc' : 'desc';

        if ($filters->sortBy() === 'relevance' && $filters->q() !== null) {
            return $this->relevance($query, $filters->q());
        }

        return match ($filters->sortBy()) {
            'nomor_faktur' => $this->sort($query, 'supplier_invoice_list_projection.nomor_faktur', $sortDir),
            'due_date' => $this->sort($query, 'supplier_invoice_list_projection.due_date', $sortDir),
            'nama_pt_pengirim' => $this->sort($query, 'supplier_invoice_list_projection.supplier_nama_pt_pengirim_snapshot', $sortDir),
            'grand_total_rupiah' => $this->sort($query, 'supplier_invoice_list_projection.grand_total_rupiah', $sortDir),
            'total_paid_rupiah' => $this->sort($query, 'supplier_invoice_list_projection.total_paid_rupiah', $sortDir),
            'outstanding_rupiah' => $this->sort($query, 'supplier_invoice_list_projection.outstanding_rupiah', $sortDir),
            'receipt_count' => $this->sort($query, 'supplier_invoice_list_projection.receipt_count', $sortDir),
            'total_received_qty' => $this->sort($query, 'supplier_invoice_list_projection.total_received_qty', $sortDir),
            default => $this->sort($query, 'supplier_invoice_list_projection.shipment_date', $sortDir),
        };
    }

    private function relevance(Builder $query, string $keyword): Builder
    {
        $term = mb_strtolower(trim($keyword), 'UTF-8');

        return $query->orderByRaw(
            'CASE '
            . 'WHEN supplier_invoice_list_projection.nomor_faktur_normalized = ? THEN 0 '
            . 'WHEN supplier_invoice_list_projection.nomor_faktur_normalized LIKE ? THEN 1 '
            . 'WHEN LOWER(supplier_invoice_list_projection.supplier_nama_pt_pengirim_snapshot) = ? THEN 2 '
            . 'WHEN LOWER(supplier_invoice_list_projection.supplier_nama_pt_pengirim_snapshot) LIKE ? THEN 3 '
            . 'ELSE 4 END',
            [$term, $term . '%', $term, $term . '%'],
        )->orderByDesc('supplier_invoice_list_projection.shipment_date')
            ->orderBy('supplier_invoice_list_projection.supplier_invoice_id');
    }

    private function sort(Builder $query, string $column, string $direction): Builder
    {
        return $query
            ->orderBy($column, $direction)
            ->orderBy('supplier_invoice_list_projection.supplier_invoice_id');
    }
}
