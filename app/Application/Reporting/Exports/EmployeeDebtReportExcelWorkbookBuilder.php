<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class EmployeeDebtReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly EmployeeDebtReportExcelSummarySheetWriter $summaryWriter,
        private readonly EmployeeDebtReportExcelDetailSheetWriter $detailWriter,
        private readonly EmployeeDebtReportExcelPeriodSheetWriter $periodWriter,
        private readonly EmployeeDebtReportExcelStatusSheetWriter $statusWriter,
    ) {}

    public function build(array $dataset, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $this->summaryWriter->write(
            $spreadsheet->getActiveSheet(),
            is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [],
            $filters,
            $dataset['temporal_summary_rows'] ?? [],
        );

        $this->detailWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['rows'] ?? null) ? $dataset['rows'] : [],
        );

        $this->periodWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['period_rows'] ?? null) ? $dataset['period_rows'] : [],
        );

        $this->statusWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['status_rows'] ?? null) ? $dataset['status_rows'] : [],
        );

        $this->metadata->append($spreadsheet, 'EmployeeDebtReport', 'GetEmployeeDebtReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
