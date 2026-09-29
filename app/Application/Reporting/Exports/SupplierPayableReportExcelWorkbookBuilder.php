<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class SupplierPayableReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly SupplierPayableReportExcelSummarySheetWriter $summaryWriter,
        private readonly SupplierPayableReportExcelDetailSheetWriter $detailWriter,
        private readonly SupplierPayableReportExcelPeriodSheetWriter $periodWriter,
        private readonly SupplierPayableReportExcelSupplierSheetWriter $supplierWriter,
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

        $this->supplierWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['supplier_rows'] ?? null) ? $dataset['supplier_rows'] : [],
        );

        $this->metadata->append($spreadsheet, 'SupplierPayableReport', 'GetSupplierPayableReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
