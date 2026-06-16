<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use DateTimeImmutable;

final class SupplierInvoice
{
    use SupplierInvoiceState;
    use SupplierInvoiceValidation;

    /** @param list<SupplierInvoiceLine> $lines */
    public static function create(
        string $id,
        string $supplierId,
        string $supplierNamaPtPengirimSnapshot,
        string $nomorFaktur,
        DateTimeImmutable $tanggalPengiriman,
        array $lines,
        ?SupplierInvoiceTaxSummary $taxSummary = null,
    ): self {
        self::assertValid($id, $supplierId, $supplierNamaPtPengirimSnapshot, $nomorFaktur, $lines);

        $grandTotalRupiah = self::calculateGrandTotalRupiah($lines);
        $resolvedTaxSummary = $taxSummary ?? SupplierInvoiceTaxSummary::none($grandTotalRupiah->amount());
        self::assertTaxSummaryMatchesGrandTotal($resolvedTaxSummary, $grandTotalRupiah);

        return new self(
            trim($id),
            trim($supplierId),
            trim($supplierNamaPtPengirimSnapshot),
            trim($nomorFaktur),
            'invoice',
            'active',
            null,
            null,
            $tanggalPengiriman,
            self::calculateJatuhTempo($tanggalPengiriman),
            array_values($lines),
            $grandTotalRupiah,
            $resolvedTaxSummary,
        );
    }

    /** @param list<SupplierInvoiceLine> $lines */
    public static function rehydrate(
        string $id,
        string $supplierId,
        string $supplierNamaPtPengirimSnapshot,
        string $nomorFaktur,
        string $documentKind,
        string $lifecycleStatus,
        ?string $originSupplierInvoiceId,
        ?string $supersededBySupplierInvoiceId,
        DateTimeImmutable $tanggalPengiriman,
        DateTimeImmutable $jatuhTempo,
        array $lines,
        ?SupplierInvoiceTaxSummary $taxSummary = null,
    ): self {
        self::assertValid($id, $supplierId, $supplierNamaPtPengirimSnapshot, $nomorFaktur, $lines);

        $grandTotalRupiah = self::calculateGrandTotalRupiah($lines);
        $resolvedTaxSummary = $taxSummary ?? SupplierInvoiceTaxSummary::none($grandTotalRupiah->amount());
        self::assertTaxSummaryMatchesGrandTotal($resolvedTaxSummary, $grandTotalRupiah);

        return new self(
            trim($id),
            trim($supplierId),
            trim($supplierNamaPtPengirimSnapshot),
            trim($nomorFaktur),
            trim($documentKind),
            trim($lifecycleStatus),
            self::normalizeNullableString($originSupplierInvoiceId),
            self::normalizeNullableString($supersededBySupplierInvoiceId),
            $tanggalPengiriman,
            $jatuhTempo,
            array_values($lines),
            $grandTotalRupiah,
            $resolvedTaxSummary,
        );
    }
}
