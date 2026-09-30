<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerPaymentRowsQuery
{
    public function __construct(
        private readonly TransactionCashLedgerLegacyPaymentAllocationRowsQuery $legacyPaymentRows =
            new TransactionCashLedgerLegacyPaymentAllocationRowsQuery(),
        private readonly TransactionCashLedgerComponentAllocationRowsQuery $componentPaymentRows =
            new TransactionCashLedgerComponentAllocationRowsQuery(),
        private readonly TransactionCashLedgerRefundedPaymentFallbackRowsQuery $refundedPaymentFallbackRows =
            new TransactionCashLedgerRefundedPaymentFallbackRowsQuery(),
        private readonly TransactionCashLedgerPaymentNoteLabelResolver $noteLabels =
            new TransactionCashLedgerPaymentNoteLabelResolver(),
        private readonly TransactionCashLedgerPaymentRowMapper $mapper =
            new TransactionCashLedgerPaymentRowMapper(),
    ) {}

    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        $rows = DB::query()
            ->fromSub(
                $this->legacyPaymentRows
                    ->query($fromEventDate, $toEventDate)
                    ->unionAll($this->componentPaymentRows->query($fromEventDate, $toEventDate))
                    ->unionAll($this->refundedPaymentFallbackRows->query($fromEventDate, $toEventDate)),
                'cash_payment_rows'
            )
            ->orderBy('event_date')
            ->orderBy('customer_payment_id')
            ->get([
                'note_id',
                'customer_name',
                'transaction_date',
                'event_date',
                'event_amount_rupiah',
                'customer_payment_id',
                'payment_method',
                'cash_amount_paid_rupiah',
                'cash_amount_received_rupiah',
                'cash_change_rupiah',
                'source_table',
            ]);

        return $this->mapper->map($rows, $this->noteLabels->resolve($rows));
    }
}
