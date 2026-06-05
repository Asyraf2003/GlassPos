<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class InventoryStockValueReportExcelMovementSheetWriter
{
    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Mutasi Periode');

        $headers = [
            'Product ID',
            'Kode',
            'Nama Barang',
            'Supply In',
            'Sale Out',
            'Refund/Reversal',
            'Koreksi/Revisi',
            'Qty In',
            'Qty Out',
            'Net Qty',
            'Nilai Masuk',
            'Nilai Keluar',
            'Selisih Nilai',
            'Qty Saat Ini',
            'Harga Pokok Rata-rata',
            'Nilai Persediaan Saat Ini',
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
            $sheet->setCellValue('D'.$excelRow, $this->int($row['supply_in_qty'] ?? 0));
            $sheet->setCellValue('E'.$excelRow, $this->int($row['sale_out_qty'] ?? 0));
            $sheet->setCellValue('F'.$excelRow, $this->int($row['refund_reversal_qty'] ?? 0));
            $sheet->setCellValue('G'.$excelRow, $this->int($row['revision_correction_qty'] ?? 0));
            $sheet->setCellValue('H'.$excelRow, $this->int($row['qty_in'] ?? 0));
            $sheet->setCellValue('I'.$excelRow, $this->int($row['qty_out'] ?? 0));
            $sheet->setCellValue('J'.$excelRow, $this->int($row['net_qty_delta'] ?? 0));
            $sheet->setCellValue('K'.$excelRow, $this->int($row['total_in_cost_rupiah'] ?? 0));
            $sheet->setCellValue('L'.$excelRow, $this->int($row['total_out_cost_rupiah'] ?? 0));
            $sheet->setCellValue('M'.$excelRow, $this->int($row['net_cost_delta_rupiah'] ?? 0));
            $sheet->setCellValue('N'.$excelRow, $this->int($row['current_qty_on_hand'] ?? 0));
            $sheet->setCellValue('O'.$excelRow, $this->int($row['current_avg_cost_rupiah'] ?? 0));
            $sheet->setCellValue('P'.$excelRow, $this->int($row['current_inventory_value_rupiah'] ?? 0));
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
