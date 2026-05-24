<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

final class TransactionCashLedgerReportingQuery
{
    public function __construct(
        private readonly TransactionCashLedgerPaymentRowsQuery $paymentRows,
        private readonly TransactionCashLedgerRefundRowsQuery $refundRows,
        private readonly TransactionCashLedgerSurplusRefundPaidRowsQuery $surplusRefundPaidRows,
    ) {
    }

    public function rows(string $fromEventDate, string $toEventDate): array
    {
        return $this->paymentRows->rows($fromEventDate, $toEventDate)
            ->concat($this->refundRows->rows($fromEventDate, $toEventDate))
            ->concat($this->surplusRefundPaidRows->rows($fromEventDate, $toEventDate))
            ->sortBy([['event_date', 'asc'], ['event_type', 'asc'], ['note_id', 'asc']])
            ->values()
            ->all();
    }

    public function reconciliation(string $fromEventDate, string $toEventDate): array
    {
        $rows = $this->rows($fromEventDate, $toEventDate);

        return [
            'total_in_rupiah' => $this->sumRows($rows, 'in'),
            'cash_in_rupiah' => $this->sumRows($rows, 'in', 'cash'),
            'transfer_in_rupiah' => $this->sumRows($rows, 'in', 'transfer'),
            'total_out_rupiah' => $this->sumRows($rows, 'out'),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function sumRows(array $rows, string $direction, ?string $paymentMethod = null): int
    {
        return array_sum(array_column(
            array_filter(
                $rows,
                static fn (array $row): bool => $row['direction'] === $direction
                    && ($paymentMethod === null || ($row['payment_method'] ?? null) === $paymentMethod)
            ),
            'event_amount_rupiah'
        ));
    }
}
