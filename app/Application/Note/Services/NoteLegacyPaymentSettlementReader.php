<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class NoteLegacyPaymentSettlementReader
{
    public function __construct(
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
    ) {}

    /** @return array{gross_total_rupiah:int,net_paid_rupiah:int,outstanding_rupiah:int} */
    public function resolve(Note $note): array
    {
        if ($note->isCancelled()) {
            return ['gross_total_rupiah' => 0, 'net_paid_rupiah' => 0, 'outstanding_rupiah' => 0];
        }
        $grandTotal = $note->totalRupiah()->amount();
        $allocated = $this->allocations->getTotalAllocatedAmountByNoteId($note->id())->amount();
        $grossPaid = $this->allocations->getTotalPaymentAmountByNoteId($note->id())->amount();
        $refunded = $this->refunds->getTotalRefundedAmountByNoteId($note->id())->amount();
        $netPaid = max(max($allocated, $grossPaid) - $refunded, 0);

        return [
            'gross_total_rupiah' => $grandTotal,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => max($grandTotal - $netPaid, 0),
        ];
    }
}
