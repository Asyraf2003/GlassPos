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
        $cumulativeAllocated = 0;
        $remainingBefore = max($grandTotalRupiah, 0);
        $timeline = [];

        foreach ($this->payments->findByNoteId($noteId) as $event) {
            $allocated = max((int) $event['allocated_amount_rupiah'], 0);
            $cumulativeAllocated += $allocated;
            $remainingAfter = max($grandTotalRupiah - $cumulativeAllocated, 0);
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

            $remainingBefore = $remainingAfter;
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
