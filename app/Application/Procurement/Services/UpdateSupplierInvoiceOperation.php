<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;

final class UpdateSupplierInvoiceOperation
{
    public function __construct(
        private readonly SupplierInvoiceWriterPort $writer,
        private readonly UpdatedSupplierInvoiceBuilder $builder,
        private readonly SupplierInvoiceRevisionContextResolver $contextResolver,
        private readonly SupplierInvoiceRevisionDeltaMovementsBuilder $deltaMovements,
        private readonly SupplierInvoiceRevisionDeltaStockGuard $deltaStockGuard,
        private readonly SupplierInvoiceRevisionInventoryEffectsApplier $inventoryEffects,
    ) {
    }

    public function execute(
        SupplierInvoice $current,
        string $supplierInvoiceId,
        string $nomorFaktur,
        string $namaPtPengirim,
        string $tanggalPengiriman,
        array $lines,
        null|string|int $taxInput = null,
        bool $taxRoundingResidueConfirmed = false,
    ): Result {
        $updated = $this->builder->build(
            $current,
            $nomorFaktur,
            $namaPtPengirim,
            $tanggalPengiriman,
            $lines,
            $taxInput,
            $taxRoundingResidueConfirmed,
        );

        $context = $this->contextResolver->resolve($supplierInvoiceId, $updated);

        if ($updated->grandTotalRupiah()->amount() < $context->totalPaidRupiah()) {
            return Result::failure(
                'Total revisi tidak boleh lebih kecil dari total pembayaran yang sudah tercatat.',
                ['supplier_invoice' => ['SUPPLIER_INVOICE_REVISED_TOTAL_BELOW_TOTAL_PAID']]
            );
        }

        // ADR-0037: received unit-cost revisions are handled as explicit
        // inventory cost revaluation effects instead of a terminal blocker.

        $deltaMovements = $context->totalReceivedQty() > 0
            ? $this->deltaMovements->build($current, $updated, $lines, $context->movementDate())
            : [];

        if (! $this->deltaStockGuard->canApplyWithoutNegativeStock($deltaMovements)) {
            return Result::failure(
                'Revisi faktur akan membuat stok product lama menjadi negatif.',
                ['supplier_invoice' => ['SUPPLIER_INVOICE_REVISION_NEGATIVE_STOCK']]
            );
        }

        $this->writer->update($updated);

        $inventoryEffects = $this->inventoryEffects->apply($deltaMovements);
        if ($inventoryEffects->isFailure()) {
            return $inventoryEffects;
        }

        return Result::success(['id' => $updated->id()], 'Nota supplier berhasil diperbarui.');
    }
}
