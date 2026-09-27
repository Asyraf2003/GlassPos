<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

use App\Ports\Out\Reporting\DashboardInventoryOverviewReaderPort;

final class AdminDashboardOverviewPayloadBuilder
{
    public function __construct(
        private readonly GetTransactionReportDatasetHandler $transactionReport,
        private readonly GetTransactionCashLedgerPerNoteHandler $transactionCashLedger,
        private readonly AdminDashboardSharedReportFragments $sharedFragments,
        private readonly DashboardInventoryOverviewReaderPort $inventory,
        private readonly GetOperationalProfitSummaryHandler $operationalProfit,
        private readonly GetEmployeeDebtReportDatasetHandler $employeeDebt,
        private readonly GetOperationalExpenseReportDatasetHandler $operationalExpense,
    ) {
    }

    /**
     * @param array{today:string,from:string,to:string} $period
     */
    public function build(array $period): array
    {
        $transactionSummary = ReportingResultDataExtractor::summary(
            $this->transactionReport->handle($period['from'], $period['to'])
        );

        $inventorySummary = $this->sharedFragments->inventorySummary($period);

        $operationalProfitRow = ReportingResultDataExtractor::row(
            $this->operationalProfit->handle($period['from'], $period['to'])
        );

        $employeeDebtSummary = ReportingResultDataExtractor::summary(
            $this->employeeDebt->handle($period['from'], $period['to'])
        );

        $operationalExpenseSummary = ReportingResultDataExtractor::summary(
            $this->operationalExpense->handle($period['from'], $period['to'])
        );

        $todayCash = DashboardCashLedgerTotals::fromReportResult(
            $this->transactionCashLedger->handle($period['today'], $period['today'])
        );

        $monthCash = DashboardCashLedgerTotals::fromReportResult(
            $this->transactionCashLedger->handle($period['from'], $period['to'])
        );

        $topSellingRows = $this->sharedFragments->topSellingRows($period, 5);

        $payload = AdminDashboardOverviewPayload::fromSources(
            $transactionSummary,
            $inventorySummary,
            $operationalProfitRow,
            [], // Current supplier balance is filled outside the period cache by the overview handler.
            $employeeDebtSummary,
            $operationalExpenseSummary,
            $todayCash,
            $monthCash,
            $topSellingRows,
            $this->inventory->getRestockPriorityRows(5),
        );

        $payload['period'] = [
            'today' => $period['today'],
            'active_month' => $period['active_month'] ?? substr($period['from'], 0, 7),
            'date_from' => $period['from'],
            'date_to' => $period['to'],
        ];

        return $payload;
    }
}
