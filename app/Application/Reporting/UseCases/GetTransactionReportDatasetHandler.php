<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Application\Reporting\Services\TransactionCustomerBreakdownBuilder;
use App\Application\Reporting\Services\TransactionPeriodBreakdownBuilder;
use App\Application\Reporting\Services\TransactionReportSummaryBuilder;
use App\Application\Shared\DTO\Result;

final class GetTransactionReportDatasetHandler
{
    public function __construct(
        private readonly GetTransactionSummaryPerNoteHandler $summaryHandler,
        private readonly TransactionReportSummaryBuilder $summary,
        private readonly TransactionPeriodBreakdownBuilder $periods,
        private readonly TransactionCustomerBreakdownBuilder $customers,
    ) {
    }

    public function handle(string $fromTransactionDate, string $toTransactionDate): Result
    {
        $result = $this->summaryHandler->handle($fromTransactionDate, $toTransactionDate);

        return $this->build($result);
    }

    public function handleCurrent(string $fromTransactionDate, string $toTransactionDate): Result
    {
        return $this->build($this->summaryHandler->handleCurrent($fromTransactionDate, $toTransactionDate));
    }

    private function build(Result $result): Result
    {
        if ($result->isFailure()) {
            return $result;
        }

        $data = $result->data();
        $rows = is_array($data) && is_array($data['rows'] ?? null)
            ? $data['rows']
            : [];

        return Result::success([
            'rows' => $rows,
            'summary' => $this->summary->build($rows),
            'period_rows' => $this->periods->build($rows),
            'customer_rows' => $this->customers->build($rows),
        ]);
    }
}
