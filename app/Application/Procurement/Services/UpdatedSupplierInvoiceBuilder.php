<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;
use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class UpdatedSupplierInvoiceBuilder
{
    public function __construct(
        private readonly SupplierService $supplierService,
        private readonly SupplierInvoiceFactory $invoiceFactory,
        private readonly SupplierInvoiceTaxLandedCostAllocator $taxAllocator,
    ) {
    }

    public function build(
        SupplierInvoice $current,
        string $nomorFaktur,
        string $namaPtPengirim,
        string $tanggalPengiriman,
        array $lines,
        null|string|int $taxInput = null,
    ): SupplierInvoice {
        $shipmentDate = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tanggalPengiriman))
            ?: throw new DomainException('Format tanggal pengiriman salah.');

        $supplier = $this->supplierService->resolve($namaPtPengirim);
        $taxAllocation = $this->taxAllocator->allocate($lines, $taxInput);
        $taxCalculation = $taxAllocation->tax();
        $invoiceLines = $this->invoiceFactory->makeLines($taxAllocation->lines());

        return SupplierInvoice::create(
            $current->id(),
            $supplier->id(),
            $supplier->namaPtPengirim(),
            trim($nomorFaktur),
            $shipmentDate,
            $invoiceLines,
            SupplierInvoiceTaxSummary::rehydrate(
                $taxAllocation->subtotalBeforeTaxRupiah(),
                $taxCalculation->taxInput(),
                $taxCalculation->taxMode(),
                $taxCalculation->taxRateBasisPoints(),
                $taxCalculation->taxAmountRupiah(),
            ),
        );
    }
}
