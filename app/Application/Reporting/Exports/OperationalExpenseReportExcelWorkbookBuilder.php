<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class OperationalExpenseReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly OperationalExpenseReportExcelSummarySheetWriter $summaryWriter,
        private readonly OperationalExpenseReportExcelDetailSheetWriter $detailWriter,
        private readonly OperationalExpenseReportExcelPeriodSheetWriter $periodWriter,
        private readonly OperationalExpenseReportExcelCategorySheetWriter $categoryWriter,
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

        $this->categoryWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['category_rows'] ?? null) ? $dataset['category_rows'] : [],
        );

        $this->metadata->append($spreadsheet, 'OperationalExpenseReport', 'GetOperationalExpenseReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
