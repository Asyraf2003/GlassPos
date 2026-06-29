<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class InventoryStockValueReportExcelSnapshotSheetWriter
{
    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Snapshot Stok');

        $headers = [
            'Product ID',
            'Kode',
            'Nama Barang',
            'Merek',
            'Ukuran',
            'Qty Saat Ini',
            'Harga Pokok Rata-rata',
            'Inventory Value',
            'Reorder Point',
            'Critical Threshold',
            'Nilai Avg x Qty',
            'Residual Pembulatan HPP',
            'Qty Ledger',
            'Nilai Ledger',
            'Selisih Qty Ledger',
            'Selisih Nilai Ledger',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column.'1', $header);
        }

        foreach (array_values($rows) as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;

            $this->setStringCell($sheet, 'A'.$excelRow, $row['product_id'] ?? null);
            $this->setStringCell($sheet, 'B'.$excelRow, $row['kode_barang'] ?? null);
            $this->setStringCell($sheet, 'C'.$excelRow, $row['nama_barang'] ?? null);
            $this->setStringCell($sheet, 'D'.$excelRow, $row['merek'] ?? null);
            $this->setStringCell($sheet, 'E'.$excelRow, $row['ukuran'] ?? null);
            $sheet->setCellValue('F'.$excelRow, $this->int($row['current_qty_on_hand'] ?? 0));
            $sheet->setCellValue('G'.$excelRow, $this->int($row['current_avg_cost_rupiah'] ?? 0));
            $sheet->setCellValue('H'.$excelRow, $this->int($row['current_inventory_value_rupiah'] ?? 0));
            $sheet->setCellValue('I'.$excelRow, $row['reorder_point_qty'] ?? null);
            $sheet->setCellValue('J'.$excelRow, $row['critical_threshold_qty'] ?? null);
            $sheet->setCellValue('K'.$excelRow, $this->int($row['current_inventory_value_by_average_rupiah'] ?? 0));
            $sheet->setCellValue('L'.$excelRow, $this->int($row['current_rounding_residual_rupiah'] ?? 0));
            $sheet->setCellValue('M'.$excelRow, $this->int($row['ledger_qty_on_hand'] ?? 0));
            $sheet->setCellValue('N'.$excelRow, $this->int($row['ledger_inventory_value_rupiah'] ?? 0));
            $sheet->setCellValue('O'.$excelRow, $this->int($row['ledger_qty_diff'] ?? 0));
            $sheet->setCellValue('P'.$excelRow, $this->int($row['ledger_value_diff_rupiah'] ?? 0));
        }

        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function setStringCell(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        $sheet->setCellValueExplicit($coordinate, $value === null ? '' : (string) $value, DataType::TYPE_STRING);
    }
}
