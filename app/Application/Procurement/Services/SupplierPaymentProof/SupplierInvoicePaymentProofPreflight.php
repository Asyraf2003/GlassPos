<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierInvoiceVoidStatus;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;

final class SupplierInvoicePaymentProofPreflight
{
    public function __construct(
        private readonly SupplierInvoiceReaderPort $invoices,
        private readonly SupplierPaymentReaderPort $payments,
        private readonly SupplierInvoiceVoidStatus $voidStatus,
    ) {
    }

    public function prepare(string $supplierInvoiceId): Result
    {
        $invoice = $this->invoices->getByIdForUpdate(trim($supplierInvoiceId));

        if ($invoice === null) {
            return Result::failure('Nota supplier tidak ditemukan.', [
                'supplier_invoice' => ['SUPPLIER_INVOICE_NOT_FOUND'],
            ]);
        }

        if ($this->voidStatus->isVoided($invoice->id())) {
            return Result::failure('Nota supplier yang sudah dibatalkan tidak bisa dimutasi lagi.', [
                'supplier_invoice' => ['SUPPLIER_INVOICE_VOIDED'],
            ]);
        }

        $outstanding = $invoice->grandTotalRupiah()->amount()
            - $this->payments->getTotalPaidBySupplierInvoiceId($invoice->id())->amount();

        if ($outstanding < 1) {
            return Result::failure('Invoice supplier ini sudah lunas.', [
                'supplier_payment' => ['SUPPLIER_INVOICE_ALREADY_PAID'],
            ]);
        }

        return Result::success([
            'invoice' => $invoice,
            'outstanding_rupiah' => $outstanding,
        ]);
    }
}
