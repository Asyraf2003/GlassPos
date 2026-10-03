<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface SupplierInvoiceCanonicalizationReaderPort
{
    /** @return list<string> Current operational invoice roots, ordered for locking. */
    public function invoiceIdsForProduct(string $productId): array;
}
