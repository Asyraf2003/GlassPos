<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Ports\Out\Reporting\TransactionReportingSourceReaderPort;

final class DatabaseTransactionReportingSourceReaderAdapter implements TransactionReportingSourceReaderPort
{
    public function __construct(
        private readonly TransactionSummaryReportingQuery $summaryQuery,
        private readonly TransactionCashLedgerReportingQuery $cashLedgerQuery,
    ) {
    }

    public function getCurrentTransactionSummaryPerNoteRows(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->summaryQuery->rows($fromTransactionDate, $toTransactionDate, 'current');
    }

    public function getTransactionSummaryPerNoteRows(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->summaryQuery->rows($fromTransactionDate, $toTransactionDate);
    }

    public function getTransactionSummaryPerNoteReconciliation(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->summaryQuery->reconciliation($fromTransactionDate, $toTransactionDate);
    }

    public function getTransactionCashLedgerPerNoteRows(string $fromEventDate, string $toEventDate): array
    {
        return $this->cashLedgerQuery->rows($fromEventDate, $toEventDate);
    }

    public function getTransactionCashLedgerPerNoteReconciliation(string $fromEventDate, string $toEventDate): array
    {
        return $this->cashLedgerQuery->reconciliation($fromEventDate, $toEventDate);
    }
}
