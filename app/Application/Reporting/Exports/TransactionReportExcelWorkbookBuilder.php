<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class TransactionReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly TransactionReportExcelSummarySheetWriter $summaryWriter,
        private readonly TransactionReportExcelDetailSheetWriter $detailWriter,
        private readonly TransactionReportExcelPeriodSheetWriter $periodWriter,
        private readonly TransactionReportExcelCustomerSheetWriter $customerWriter,
    ) {}

    public function build(array $dataset, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $this->summaryWriter->write(
            $spreadsheet->getActiveSheet(),
            is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [],
            $filters,
        );

        $this->detailWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [],
        );

        $this->periodWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['period_rows'] ?? null) ? $dataset['period_rows'] : [],
        );

        $this->customerWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['customer_rows'] ?? null) ? $dataset['customer_rows'] : [],
        );

        $this->metadata->append($spreadsheet, 'TransactionReport', 'GetTransactionReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
