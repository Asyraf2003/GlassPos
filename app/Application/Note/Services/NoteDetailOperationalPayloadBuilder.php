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
        $subtotalBeforeNoteTax = (int) ($totals['subtotal_before_note_tax_rupiah'] ?? $grandTotal);
        $noteTaxInput = $totals['note_tax_input'] ?? null;
        $noteTaxMode = (string) ($totals['note_tax_mode'] ?? 'none');
        $noteTaxRateBasisPoints = $totals['note_tax_rate_basis_points'] ?? null;
        $noteTaxAmount = (int) ($totals['note_tax_amount_rupiah'] ?? 0);
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
            'subtotal_before_note_tax_rupiah' => $subtotalBeforeNoteTax,
            'note_tax_input' => is_scalar($noteTaxInput) ? (string) $noteTaxInput : null,
            'note_tax_mode' => $noteTaxMode,
            'note_tax_rate_basis_points' => $noteTaxRateBasisPoints !== null ? (int) $noteTaxRateBasisPoints : null,
            'note_tax_amount_rupiah' => $noteTaxAmount,
            'total_allocated_rupiah' => $allocated,
            'total_refunded_rupiah' => $refunded,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
        ];
    }
}
