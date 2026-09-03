<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Shared\ValueObjects\Money;

interface SupplierPaymentReaderPort
{
    public function getTotalPaidBySupplierInvoiceId(string $supplierInvoiceId): Money;

    public function getById(string $supplierPaymentId): ?SupplierPayment;

    public function getByIdForUpdate(string $supplierPaymentId): ?SupplierPayment;

    /**
     * @return list<SupplierPayment>
     */
    public function listBySupplierInvoiceId(string $supplierInvoiceId): array;
}
