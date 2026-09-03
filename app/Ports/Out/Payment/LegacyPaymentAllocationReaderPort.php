<?php

declare(strict_types=1);

namespace App\Ports\Out\Payment;

interface LegacyPaymentAllocationReaderPort
{
    /** @return list<object> */
    public function listWithoutComponentAllocations(string $noteId): array;
}
