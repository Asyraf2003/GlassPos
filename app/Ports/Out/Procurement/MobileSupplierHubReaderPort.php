<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface MobileSupplierHubReaderPort
{
    /** @return list<array<string, int|string>> */
    public function outstandingInvoices(int $limit = 100): array;

    /** @return list<array<string, int|string>> */
    public function recentPaymentProofs(int $limit = 100): array;
}
