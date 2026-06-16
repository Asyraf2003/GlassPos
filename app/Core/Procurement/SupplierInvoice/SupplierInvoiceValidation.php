<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

trait SupplierInvoiceValidation
{
    /** @param list<SupplierInvoiceLine> $lines */
    private static function assertValid(
        string $id,
        string $supplierId,
        string $supplierNamaPtPengirimSnapshot,
        string $nomorFaktur,
        array $lines
    ): void {
        if (trim($id) === '') throw new DomainException('ID wajib ada.');
        if (trim($supplierId) === '') throw new DomainException('Supplier ID wajib ada.');
        if (trim($supplierNamaPtPengirimSnapshot) === '') throw new DomainException('Snapshot nama supplier wajib ada.');
        if (trim($nomorFaktur) === '') throw new DomainException('Nomor faktur wajib ada.');
        if ($lines === []) throw new DomainException('Minimal 1 line.');

        foreach ($lines as $line) {
            if (! $line instanceof SupplierInvoiceLine) {
                throw new DomainException('Line tidak valid.');
            }
        }

        self::assertNoDuplicateProducts($lines);
    }

    /** @param list<SupplierInvoiceLine> $lines */
    private static function assertNoDuplicateProducts(array $lines): void
    {
        $seen = [];

        foreach ($lines as $line) {
            $productId = trim($line->productId());
            $lineNo = $line->lineNo();

            if (array_key_exists($productId, $seen)) {
                throw new DomainException(
                    'Baris ' . $lineNo . ': produk yang sama sudah dipakai di baris ' . $seen[$productId] . '.'
                );
            }

            $seen[$productId] = $lineNo;
        }
    }

    private static function calculateJatuhTempo(DateTimeImmutable $tanggalPengiriman): DateTimeImmutable
    {
        $month = (int) $tanggalPengiriman->format('n') + 1;
        $year = (int) $tanggalPengiriman->format('Y');

        if ($month > 12) {
            $month = 1;
            $year++;
        }

        $lastDay = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))
            ->modify('last day of this month')
            ->format('j');

        return $tanggalPengiriman->setDate($year, $month, min((int) $tanggalPengiriman->format('j'), $lastDay));
    }

    /** @param list<SupplierInvoiceLine> $lines */
    private static function calculateGrandTotalRupiah(array $lines): Money
    {
        $total = Money::zero();

        foreach ($lines as $line) {
            $total = $total->add($line->lineTotalRupiah());
        }

        return $total;
    }

    private static function assertTaxSummaryMatchesGrandTotal(SupplierInvoiceTaxSummary $taxSummary, Money $grandTotalRupiah): void
    {
        if ($taxSummary->grandTotalAfterTaxRupiah()->amount() !== $grandTotalRupiah->amount()) {
            throw new DomainException('Grand total supplier invoice tidak cocok dengan subtotal dan pajak.');
        }
    }
}
