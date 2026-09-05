<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

final class AdminNoteHistoryProjectionItemMapper
{
    public function __construct(
        private readonly CashierNoteHistoryValueFormatter $formatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function map(object $row): array
    {
        $grandTotal = (int) $row->total_rupiah;
        $netPaid = (int) $row->net_paid_rupiah;
        $outstanding = (int) $row->outstanding_rupiah;

        $lineOpenCount = (int) $row->line_open_count;
        $lineCloseCount = (int) $row->line_close_count;
        $lineRefundCount = (int) $row->line_refund_count;

        return [
            'note_id' => (string) $row->note_id,
            'transaction_date' => $this->formatter->date($row->transaction_date),
            'created_at_text' => $this->formatter->dateTime($row->note_created_at ?? null, (string) $row->transaction_date),
            'note_number' => (string) $row->note_id,
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
            'action_label' => 'Pilih',
            'action_url' => route('admin.notes.show', ['noteId' => (string) $row->note_id]),
        ];
    }
}
