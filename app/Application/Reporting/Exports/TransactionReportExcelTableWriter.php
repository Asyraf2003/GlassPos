<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionReportExcelTableWriter
{
    public function writeTable(Worksheet $sheet, int $startRow, array $headers, array $rows): void
    {
        $this->writeRow($sheet, $startRow, $headers);
        $sheet->getStyle('A'.$startRow.':'.Coordinate::stringFromColumnIndex(count($headers)).$startRow)
            ->getFont()
            ->setBold(true);

        foreach ($rows as $offset => $row) {
            $this->writeRow($sheet, $startRow + $offset + 1, $row);
        }

        $sheet->freezePane('A'.($startRow + 1));
    }

    public function autosize(Worksheet $sheet, int $columns): void
    {
        for ($column = 1; $column <= $columns; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function writeRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $coordinate = Coordinate::stringFromColumnIndex($index + 1).$rowNumber;

            if (is_string($value)) {
                $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);

                continue;
            }

            $sheet->setCellValue($coordinate, $value);
        }
    }
}
