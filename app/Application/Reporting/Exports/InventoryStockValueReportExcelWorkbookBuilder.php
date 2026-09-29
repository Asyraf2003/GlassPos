<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class InventoryStockValueReportExcelWorkbookBuilder
{
    public function __construct(
        private readonly ReportWorkbookMetadataWriter $metadata,
        private readonly InventoryStockValueReportExcelSummarySheetWriter $summaryWriter,
        private readonly InventoryStockValueReportExcelSnapshotSheetWriter $snapshotWriter,
        private readonly InventoryStockValueReportExcelMovementSheetWriter $movementWriter,
    ) {}

    public function build(array $dataset, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $this->summaryWriter->write(
            $spreadsheet->getActiveSheet(),
            is_array($dataset['summary'] ?? null) ? $dataset['summary'] : [],
            $filters,
        );

        $this->snapshotWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['snapshot_rows'] ?? null) ? $dataset['snapshot_rows'] : [],
        );

        $this->movementWriter->write(
            $spreadsheet->createSheet(),
            is_array($dataset['movement_rows'] ?? null) ? $dataset['movement_rows'] : [],
        );

        $diagnostics = $spreadsheet->createSheet();
        $this->snapshotWriter->write($diagnostics, $dataset['current_diagnostic_rows'] ?? []);
        $diagnostics->setTitle('Diagnostik Saat Ini');
        $diagnostics->setCellValue('F1', 'Qty Saat Ini');

        $this->metadata->append($spreadsheet, 'InventoryStockValueReport', 'GetInventoryStockValueReportDatasetHandler', $filters);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
