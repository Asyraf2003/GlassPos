<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierInvoiceVoidStatus;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;

final class SupplierPaymentProofPrepareScopePreflight
{
    public function __construct(
        private readonly SupplierInvoiceReaderPort $invoices,
        private readonly SupplierPaymentReaderPort $payments,
        private readonly SupplierInvoiceVoidStatus $voidStatus,
    ) {}

    public function check(string $scopeType, string $scopeId): Result
    {
        if ($scopeType === 'supplier_payment') {
            return $this->payments->getById($scopeId) === null
                ? Result::failure('Pembayaran supplier tidak ditemukan.', ['scope' => ['SUPPLIER_PAYMENT_NOT_FOUND']])
                : Result::success();
        }

        $invoice = $this->invoices->getById($scopeId);

        if ($invoice === null) {
            return Result::failure('Nota supplier tidak ditemukan.', ['scope' => ['SUPPLIER_INVOICE_NOT_FOUND']]);
        }

        if ($this->voidStatus->isVoided($invoice->id())) {
            return Result::failure('Nota supplier yang sudah dibatalkan tidak bisa dimutasi lagi.', [
                'scope' => ['SUPPLIER_INVOICE_VOIDED'],
            ]);
        }

        $outstanding = $invoice->grandTotalRupiah()->amount()
            - $this->payments->getTotalPaidBySupplierInvoiceId($invoice->id())->amount();

        return $outstanding > 0
            ? Result::success()
            : Result::failure('Invoice supplier ini sudah lunas.', ['scope' => ['SUPPLIER_INVOICE_ALREADY_PAID']]);
    }
}
