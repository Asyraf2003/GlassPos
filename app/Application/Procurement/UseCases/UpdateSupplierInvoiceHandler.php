<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierInvoiceEditabilityGuard;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\UpdateSupplierInvoiceOperation;
use App\Application\Procurement\Services\UpdateSupplierInvoiceTransactionalRunner;
use App\Application\Procurement\Services\VoidedSupplierInvoiceGuard;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\ProcurementInvoiceDetailReaderPort;

final class UpdateSupplierInvoiceHandler
{
    public function __construct(
        private readonly SupplierInvoiceReaderPort $reader,
        private readonly ProcurementInvoiceDetailReaderPort $details,
        private readonly SupplierInvoiceEditabilityGuard $guard,
        private readonly UpdateSupplierInvoiceTransactionalRunner $transactionalRunner,
        private readonly UpdateSupplierInvoiceOperation $operation,
        private readonly VoidedSupplierInvoiceGuard $voidedGuard,
        private readonly SupplierInvoiceListProjectionService $projection,
    ) {
    }

    public function handle(
        string $supplierInvoiceId,
        string $nomorFaktur,
        string $namaPtPengirim,
        string $tanggalPengiriman,
        array $lines,
        ?int $expectedRevisionNo = null,
        ?string $changeReason = null,
        ?string $performedByActorId = null,
        ?string $performedByActorRole = null,
        string $sourceChannel = 'web_admin',
        null|string|int $taxInput = null,
        bool $taxRoundingResidueConfirmed = false,
    ): Result {
        return $this->transactionalRunner->run(
            function () use (
                $expectedRevisionNo,
                $changeReason,
                $supplierInvoiceId,
                $nomorFaktur,
                $namaPtPengirim,
                $tanggalPengiriman,
                $lines,
                $taxInput,
                $taxRoundingResidueConfirmed
            ): Result {
                $current = $this->reader->getByIdForUpdate($supplierInvoiceId);
                if ($current === null) {
                    return Result::failure('Nota supplier tidak ditemukan.', ['supplier_invoice' => ['SUPPLIER_INVOICE_NOT_FOUND']]);
                }
                $voided = $this->voidedGuard->ensureNotVoided($supplierInvoiceId);
                if ($voided->isFailure()) {
                    return $voided;
                }
                if ($expectedRevisionNo === null || $changeReason === null || trim($changeReason) === '') {
                    $editable = $this->guard->ensureEditable($supplierInvoiceId);
                    if ($editable->isFailure()) {
                        return $editable;
                    }
                }
                $detail = $this->details->getById($supplierInvoiceId);
                if ($expectedRevisionNo !== null && $expectedRevisionNo !== (int) ($detail['summary']['last_revision_no'] ?? 0)) {
                    return Result::failure('Faktur sudah berubah. Muat ulang revisi saat ini sebelum menyimpan.', ['supplier_invoice' => ['SUPPLIER_INVOICE_STALE_REVISION']]);
                }

                $result = $this->operation->execute(
                    $current,
                    $supplierInvoiceId,
                    $nomorFaktur,
                    $namaPtPengirim,
                    $tanggalPengiriman,
                    $lines,
                    $taxInput,
                    $taxRoundingResidueConfirmed,
                );

                if (! $result->isFailure()) {
                    $this->projection->syncInvoice($supplierInvoiceId);
                }

                return $result;
            },
            $performedByActorId,
            $performedByActorRole,
            $sourceChannel,
            $changeReason,
        );
    }
}
