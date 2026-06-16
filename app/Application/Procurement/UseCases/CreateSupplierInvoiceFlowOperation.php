<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierInvoiceAutoReceiveProcessor;
use App\Application\Procurement\Services\SupplierInvoiceFactory;
use App\Application\Procurement\Services\SupplierInvoiceFlowDateResolver;
use App\Application\Procurement\Services\SupplierInvoiceTaxLandedCostAllocator;
use App\Application\Procurement\Services\SupplierService;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\UuidPort;

final class CreateSupplierInvoiceFlowOperation
{
    public function __construct(
        private readonly SupplierInvoiceWriterPort $invoiceWriter,
        private readonly UuidPort $uuid,
        private readonly SupplierService $supplierService,
        private readonly SupplierInvoiceFactory $invoiceFactory,
        private readonly SupplierInvoiceTaxLandedCostAllocator $taxAllocator,
        private readonly SupplierInvoiceFlowDateResolver $dateResolver,
        private readonly SupplierInvoiceAutoReceiveProcessor $autoReceiveProcessor,
    ) {
    }

    public function execute(
        string $nomorFaktur,
        string $pt,
        string $tglKirim,
        array $lines,
        null|string|int $taxInput = null,
        bool $autoRec = true,
        ?string $tglTerima = null,
    ): SupplierInvoice {
        [$dateKirim, $dateTerima] = $this->dateResolver->resolve($tglKirim, $autoRec, $tglTerima);
        $supplier = $this->supplierService->resolve($pt);
        $taxAllocation = $this->taxAllocator->allocate($lines, $taxInput);
        $taxCalculation = $taxAllocation->tax();

        $invoice = SupplierInvoice::create(
            $this->uuid->generate(),
            $supplier->id(),
            $supplier->namaPtPengirim(),
            trim($nomorFaktur),
            $dateKirim,
            $this->invoiceFactory->makeLines($taxAllocation->lines()),
            SupplierInvoiceTaxSummary::rehydrate(
                $taxAllocation->subtotalBeforeTaxRupiah(),
                $taxCalculation->taxInput(),
                $taxCalculation->taxMode(),
                $taxCalculation->taxRateBasisPoints(),
                $taxCalculation->taxAmountRupiah(),
            ),
        );

        $this->invoiceWriter->create($invoice);

        if ($autoRec && $dateTerima !== null) {
            $this->autoReceiveProcessor->process($invoice, $dateTerima);
        }

        return $invoice;
    }
}
