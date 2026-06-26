<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Core\Note\Note\Note;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;

final class NoteOperationalStatusResolver
{
    public function __construct(
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly NoteOperationalStatusEvaluator $statuses,
        private readonly ?NoteCurrentRevisionResolver $currentRevision = null,
        private readonly ?CurrentRevisionRowSettlementProjector $currentRevisionSettlements = null,
    ) {
    }

    /**
     * @return array{
     * operational_status: string,
     * is_open: bool,
     * is_close: bool,
     * grand_total_rupiah: int,
     * total_allocated_rupiah: int,
     * total_refunded_rupiah: int,
     * net_paid_rupiah: int,
     * outstanding_rupiah: int
     * }
     */
    public function resolve(Note $note): array
    {
        $grandTotal = $note->totalRupiah()->amount();

        $allocated = $this->allocations->getTotalAllocatedAmountByNoteId($note->id());
        $allocated->ensureNotNegative('Total alokasi pada note tidak boleh negatif.');

        $grossPaid = $this->allocations->getTotalPaymentAmountByNoteId($note->id());
        $grossPaid->ensureNotNegative('Total pembayaran pada note tidak boleh negatif.');

        $refunded = $this->refunds->getTotalRefundedAmountByNoteId($note->id());
        $refunded->ensureNotNegative('Total refund pada note tidak boleh negatif.');

        $paidBasis = max($allocated->amount(), $grossPaid->amount());
        $netPaidRupiah = max($paidBasis - $refunded->amount(), 0);
        $currentSettlement = $this->currentRevisionSettlement($note);

        if ($currentSettlement !== null) {
            $grandTotal = $currentSettlement['gross_total_rupiah'];
            $netPaidRupiah = $currentSettlement['net_paid_rupiah'];
        }

        $status = $this->statuses->resolve($grandTotal, $netPaidRupiah);

        return [
            'operational_status' => $status,
            'is_open' => $status === NoteOperationalStatusEvaluator::STATUS_OPEN,
            'is_close' => $status === NoteOperationalStatusEvaluator::STATUS_CLOSE,
            'grand_total_rupiah' => $grandTotal,
            'total_allocated_rupiah' => $allocated->amount(),
            'total_refunded_rupiah' => $refunded->amount(),
            'net_paid_rupiah' => $netPaidRupiah,
            'outstanding_rupiah' => max($grandTotal - $netPaidRupiah, 0),
        ];
    }

    public function isOpen(Note $note): bool
    {
        return $this->resolve($note)['is_open'];
    }

    public function isClose(Note $note): bool
    {
        return $this->resolve($note)['is_close'];
    }

    /** @return array{gross_total_rupiah:int,net_paid_rupiah:int,outstanding_rupiah:int}|null */
    private function currentRevisionSettlement(Note $note): ?array
    {
        if ($this->currentRevision === null || $this->currentRevisionSettlements === null) {
            return null;
        }

        if (! $this->currentRevision->hasRevision($note->id())) {
            return null;
        }

        $revision = $this->currentRevision->resolveOrFail($note->id());

        if ($revision->grandTotalRupiah() <= 0) {
            return [
                'gross_total_rupiah' => 0,
                'net_paid_rupiah' => 0,
                'outstanding_rupiah' => 0,
            ];
        }

        $settlements = $this->currentRevisionSettlements->build($revision->noteRootId(), $revision->lines());
        $netPaid = 0;
        $outstanding = 0;

        foreach ($revision->lines() as $line) {
            $key = $line->workItemRootId() ?? $line->id();
            $settlement = $settlements[$key] ?? [];
            $netPaid += (int) ($settlement['net_paid_rupiah'] ?? 0);
            $outstanding += (int) ($settlement['outstanding_rupiah'] ?? $line->subtotalRupiah());
        }

        return [
            'gross_total_rupiah' => $revision->grandTotalRupiah(),
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
        ];
    }
}
