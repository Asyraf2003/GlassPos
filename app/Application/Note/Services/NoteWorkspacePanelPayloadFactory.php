<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class NoteWorkspacePanelPayloadFactory
{
    public function __construct(
        private readonly NoteLineSummaryBuilder $lineSummary,
    ) {
    }

    public function build(
        string $noteId,
        string $customerName,
        ?string $customerPhone,
        string $transactionDate,
        int $grandTotal,
        array $rows,
    ): array {
        $summary = $this->lineSummary->build($rows);

        $allocated = 0;
        $refunded = 0;
        $netPaid = 0;
        $outstanding = 0;

        foreach ($rows as $row) {
            $allocated += (int) ($row['allocated_rupiah'] ?? 0);
            $refunded += (int) ($row['refunded_rupiah'] ?? 0);
            $netPaid += (int) ($row['net_paid_rupiah'] ?? 0);
            $outstanding += (int) ($row['outstanding_rupiah'] ?? 0);
        }

        return [
            'note_header' => [
                'id' => $noteId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'transaction_date' => $transactionDate,
            ],
            'note_totals' => [
                'grand_total_rupiah' => $grandTotal,
                'total_allocated_rupiah' => $allocated,
                'total_refunded_rupiah' => $refunded,
                'net_paid_rupiah' => $netPaid,
                'outstanding_rupiah' => $outstanding,
            ],
            'line_summary' => $summary,
            'rows' => $rows,
        ];
    }
}
