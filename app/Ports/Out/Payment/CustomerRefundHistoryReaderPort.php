<?php

declare(strict_types=1);

namespace App\Ports\Out\Payment;

use App\Core\Payment\CustomerRefund\CustomerRefund;

interface CustomerRefundHistoryReaderPort
{
    /**
     * @return list<CustomerRefund>
     */
    public function listByNoteId(string $noteId): array;
}
