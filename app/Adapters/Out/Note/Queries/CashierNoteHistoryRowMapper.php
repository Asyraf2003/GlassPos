<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

use App\Core\Note\Note\Note;

final class CashierNoteHistoryRowMapper
{
    public function __construct(
        private readonly CashierNoteHistoryValueFormatter $formatter,
    ) {}

    /**
     * @param  array<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    public function map(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $grandTotal = (int) $row->total_rupiah;
            $netPaid = max((int) ($row->net_paid_rupiah ?? 0), 0);
            $outstanding = max((int) ($row->outstanding_rupiah ?? 0), 0);
            $transactionDate = (string) $row->transaction_date;

            $lineOpenCount = (int) ($row->line_open_count ?? 0);
            $lineCloseCount = (int) ($row->line_close_count ?? 0);
            $lineRefundCount = (int) ($row->line_refund_count ?? 0);

            $workOpenCount = (int) ($row->open_count ?? 0);
            $workDoneCount = (int) ($row->done_count ?? 0);
            $workCanceledCount = (int) ($row->canceled_count ?? 0);
            $noteState = (string) ($row->note_state ?? Note::STATE_OPEN);

            $items[] = [
                'note_id' => (string) $row->id,
                'transaction_date' => $this->formatter->date($transactionDate),
                'transaction_at_text' => $this->formatter->dateTime($row->created_at ?? null, $transactionDate),
                'note_number' => (string) $row->id,
                'customer_name' => $this->formatter->customerLabel(
                    (string) $row->customer_name,
                    $row->customer_phone !== null ? (string) $row->customer_phone : null,
                ),
                'grand_total_text' => $this->formatter->rupiah($grandTotal),
                'total_paid_text' => $this->formatter->rupiah($netPaid),
                'outstanding_text' => $this->formatter->rupiah($outstanding),

                'line_summary_label' => $this->formatter->lineSummary(
                    $lineOpenCount,
                    $lineCloseCount,
                    $lineRefundCount,
                ),
                'line_summary_counts' => [
                    'open' => $lineOpenCount,
                    'close' => $lineCloseCount,
                    'refund' => $lineRefundCount,
                ],

                'payment_status_label' => $outstanding <= 0 ? 'Lunas' : ($netPaid > 0 ? 'Dibayar Sebagian' : 'Belum Dibayar'),
                'work_status_label' => $this->formatter->workSummary(
                    $workOpenCount,
                    $workDoneCount,
                    $workCanceledCount,
                ),
                'focus_status_label' => $this->formatter->focusStatus($noteState, $outstanding, $workOpenCount),
                'domain_status_label' => $this->formatter->domainStatus($noteState, $workCanceledCount),
                'detail_url' => route('cashier.notes.show', ['noteId' => (string) $row->id]),
                'edit_url' => $noteState === Note::STATE_REFUNDED
                    ? null
                    : route('cashier.notes.workspace.edit', ['noteId' => (string) $row->id]),
                'can_edit' => $noteState !== Note::STATE_REFUNDED,
                'action_label' => 'Detail',
                'action_url' => route('cashier.notes.show', ['noteId' => (string) $row->id]),
            ];
        }

        return $items;
    }
}
