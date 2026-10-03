<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Application\ProductCatalog\DTO\ProductIdentityMerge;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Procurement\SupplierInvoiceCanonicalizationReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\UuidPort;

/** Identity-only revisions: never invokes inventory reconciliation or changes line economics. */
final class CanonicalizeSupplierInvoiceProduct
{
    public function __construct(
        private readonly SupplierInvoiceCanonicalizationReaderPort $affected,
        private readonly SupplierInvoiceReaderPort $reader,
        private readonly SupplierInvoiceWriterPort $writer,
        private readonly ProductReaderPort $products,
        private readonly UuidPort $uuid,
        private readonly SupplierInvoiceListProjectionService $projection,
    ) {}

    public function apply(ProductIdentityMerge $merge): int
    {
        $target = $this->products->getById($merge->canonicalProductId);
        if ($target === null) {
            throw new DomainException('Canonical product is not active.');
        }
        $count = 0;
        foreach ($this->affected->invoiceIdsForProduct($merge->sourceProductId) as $id) {
            $current = $this->reader->getByIdForUpdate($id);
            if ($current === null || ! array_filter($current->lines(), fn ($line) => $line->productId() === $merge->sourceProductId)) {
                continue;
            }
            // Existing aggregate uniqueness remains authoritative; collisions roll back the whole adoption.
            $lines = array_map(function (SupplierInvoiceLine $line) use ($merge, $target): SupplierInvoiceLine {
                $replace = $line->productId() === $merge->sourceProductId;
                return SupplierInvoiceLine::rehydrate(
                    $this->uuid->generate(), $line->lineNo(), $replace ? $target->id() : $line->productId(),
                    $replace ? $target->kodeBarang() : $line->productKodeBarangSnapshot(),
                    $replace ? $target->namaBarang() : $line->productNamaBarangSnapshot(),
                    $replace ? $target->merek() : $line->productMerekSnapshot(),
                    $replace ? $target->ukuran() : $line->productUkuranSnapshot(),
                    $line->qtyPcs(), $line->lineTotalRupiah(), $line->unitCostRupiah(),
                    $line->lineSubtotalBeforeTaxRupiah(), $line->taxInput(), $line->taxMode(),
                    $line->taxRateBasisPoints(), $line->taxAmountRupiah(), $line->roundingResidueRupiah(),
                );
            }, $current->lines());
            $updated = SupplierInvoice::rehydrate(
                $current->id(), $current->supplierId(), $current->supplierNamaPtPengirimSnapshot(), $current->nomorFaktur(),
                $current->documentKind(), $current->lifecycleStatus(), $current->originSupplierInvoiceId(),
                $current->supersededBySupplierInvoiceId(), $current->tanggalPengiriman(), $current->jatuhTempo(),
                $lines, $current->taxSummary(),
            );
            $this->writer->update($updated);
            $this->projection->syncInvoice($id);
            $count++;
        }
        return $count;
    }
}
