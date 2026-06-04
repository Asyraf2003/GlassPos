<?php

declare(strict_types=1);

namespace App\Ports\Out\Payment;

use App\Core\Shared\ValueObjects\Money;

interface PaymentAllocationReaderPort
{
    public function getTotalAllocatedAmountByNoteId(string $noteId): Money;

    public function getTotalPaymentAmountByNoteId(string $noteId): Money;

    public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money;
}
