<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class NoteDetailOperationalPayloadBuilder
{
    public function __construct(
        private readonly NoteOperationalStatusEvaluator $statuses,
    ) {
    }

    /**
     * @param array<string, mixed> $totals
     * @return array<string, mixed>
     */
    public function build(array $totals): array
    {
        $grandTotal = (int) ($totals['grand_total_rupiah'] ?? 0);
        $allocated = (int) ($totals['total_allocated_rupiah'] ?? 0);
        $refunded = (int) ($totals['total_refunded_rupiah'] ?? 0);
        $netPaid = (int) ($totals['net_paid_rupiah'] ?? 0);
        $outstanding = (int) ($totals['outstanding_rupiah'] ?? max($grandTotal - $netPaid, 0));
        $status = $this->statuses->resolve($grandTotal, $netPaid);

        return [
            'operational_status' => $status,
            'is_open' => $status === NoteOperationalStatusEvaluator::STATUS_OPEN,
            'is_close' => $status === NoteOperationalStatusEvaluator::STATUS_CLOSE,
            'grand_total_rupiah' => $grandTotal,
            'total_allocated_rupiah' => $allocated,
            'total_refunded_rupiah' => $refunded,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
        ];
    }
}
