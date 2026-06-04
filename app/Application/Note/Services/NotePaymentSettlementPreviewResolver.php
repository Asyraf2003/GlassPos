<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class NotePaymentSettlementPreviewResolver
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
    ) {
    }

    public function preview(string $noteId): Result
    {
        $note = $this->notes->getById(trim($noteId));

        if ($note === null) {
            return Result::failure('Nota tidak ditemukan.', ['payment' => ['PAYMENT_INVALID_TARGET']]);
        }

        $grandTotal = $note->totalRupiah()->amount();
        $allocated = $this->allocations->getTotalAllocatedAmountByNoteId($note->id())->amount();
        $grossPaid = $this->allocations->getTotalPaymentAmountByNoteId($note->id())->amount();
        $refunded = $this->refunds->getTotalRefundedAmountByNoteId($note->id())->amount();
        $netPaid = max(max($allocated, $grossPaid) - $refunded, 0);
        $outstanding = max($grandTotal - $netPaid, 0);

        return Result::success([
            'amount_rupiah' => $outstanding,
            'grand_total_rupiah' => $grandTotal,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
            'surplus_rupiah' => max($netPaid - $grandTotal, 0),
            'explanation' => [
                'basis' => 'backend_outstanding_settlement',
                'gross_total_rupiah' => $grandTotal,
                'net_paid_rupiah' => $netPaid,
                'outstanding_rupiah' => $outstanding,
            ],
        ]);
    }
}
