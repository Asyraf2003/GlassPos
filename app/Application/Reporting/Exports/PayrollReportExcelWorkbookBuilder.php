<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class PayrollReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly PayrollReportExcelSummarySheetWriter $summaryWriter,
        private readonly PayrollReportExcelDetailSheetWriter $detailWriter,
        private readonly PayrollReportExcelPeriodSheetWriter $periodWriter,
        private readonly PayrollReportExcelModeSheetWriter $modeWriter,
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

        $this->modeWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['mode_rows'] ?? null) ? $dataset['mode_rows'] : [],
        );

        $this->metadata->append($spreadsheet, 'PayrollReport', 'GetPayrollReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
