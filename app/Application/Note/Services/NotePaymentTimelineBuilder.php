<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Ports\Out\Payment\NotePaymentTimelineReaderPort;

final class NotePaymentTimelineBuilder
{
    public function __construct(
        private readonly NotePaymentTimelineReaderPort $payments,
    ) {}

    /** @return list<array<string, mixed>> */
    public function build(string $noteId, int $grandTotalRupiah): array
    {
        $cumulativePaid = 0;
        $timeline = [];

        foreach ($this->payments->findByNoteId($noteId) as $event) {
            $allocated = max((int) $event['allocated_amount_rupiah'], 0);
            $eventPayable = max((int) ($event['payable_total_rupiah'] ?? $grandTotalRupiah), 0);
            $refundedBefore = max((int) ($event['refunded_before_rupiah'] ?? 0), 0);
            $netPaidBefore = max($cumulativePaid - $refundedBefore, 0);
            $paymentAmount = max((int) $event['payment_amount_rupiah'], 0);
            $remainingBefore = max($eventPayable - $netPaidBefore, 0);
            $remainingAfter = max($eventPayable - $netPaidBefore - $paymentAmount, 0);
            $cumulativePaid += $paymentAmount;
            $paymentMethod = (string) $event['payment_method'];

            $timeline[] = $event + [
                'payment_method_label' => $this->paymentMethodLabel($paymentMethod),
                'is_cash' => $paymentMethod === 'cash',
                'has_cash_detail' => $paymentMethod === 'cash'
                    && $event['amount_received_rupiah'] !== null
                    && $event['change_rupiah'] !== null,
                'semantic_label' => $remainingBefore > 0 && $remainingAfter === 0
                    ? 'Pelunasan'
                    : 'Bayar Sebagian',
                'remaining_after_rupiah' => $remainingAfter,
                'show_allocated_amount' => (int) $event['payment_amount_rupiah'] !== $allocated,
            ];
        }

        return array_reverse($timeline);
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' => 'Cash',
            'transfer', 'tf' => 'Transfer',
            default => 'Metode tidak tercatat',
        };
    }
}
