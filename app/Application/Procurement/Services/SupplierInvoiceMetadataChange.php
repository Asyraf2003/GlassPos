<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;

/** ADR-0047: only invoice number is administrative; supplier/date remain revision inputs. */
final class SupplierInvoiceMetadataChange
{
    public function matches(SupplierInvoice $current, string $supplier, string $date, array $lines, null|string|int $tax): bool
    {
        if (trim($supplier) !== $current->supplierNamaPtPengirimSnapshot()
            || trim($date) !== $current->tanggalPengiriman()->format('Y-m-d')
            || trim((string) $tax) !== trim((string) $current->taxInput())
            || count($lines) !== count($current->lines())) {
            return false;
        }

        $existing = [];
        foreach ($current->lines() as $line) {
            $existing[$line->id()] = $line;
        }
        foreach ($lines as $input) {
            $id = trim((string) ($input['previous_line_id'] ?? ''));
            $line = $existing[$id] ?? null;
            if ($line === null
                || trim((string) ($input['product_id'] ?? '')) !== $line->productId()
                || (int) ($input['line_no'] ?? 0) !== $line->lineNo()
                || (int) ($input['qty_pcs'] ?? 0) !== $line->qtyPcs()
                || (int) ($input['line_total_rupiah'] ?? 0) !== $line->lineSubtotalBeforeTaxRupiah()->amount()
                || trim((string) ($input['tax_input'] ?? '')) !== trim((string) $line->taxInput())) {
                return false;
            }
            unset($existing[$id]);
        }
        return $existing === [];
    }

    public function correctNumber(SupplierInvoice $current, string $number): SupplierInvoice
    {
        return SupplierInvoice::rehydrate(
            $current->id(), $current->supplierId(), $current->supplierNamaPtPengirimSnapshot(), trim($number),
            $current->documentKind(), $current->lifecycleStatus(), $current->originSupplierInvoiceId(),
            $current->supersededBySupplierInvoiceId(), $current->tanggalPengiriman(), $current->jatuhTempo(),
            $current->lines(), SupplierInvoiceTaxSummary::rehydrate(
                $current->subtotalBeforeTaxRupiah()->amount(), $current->taxInput(), $current->taxMode(),
                $current->taxRateBasisPoints(), $current->taxAmountRupiah()->amount(),
            ),
        );
    }
}
