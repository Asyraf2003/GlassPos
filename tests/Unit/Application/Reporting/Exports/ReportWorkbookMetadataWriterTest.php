<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\ReportWorkbookMetadataWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class ReportWorkbookMetadataWriterTest extends TestCase
{
    public function test_metadata_preserves_filters_as_text_without_changing_numeric_report_cells(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->setCellValue('B1', 1);
        $filters = ['date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'search' => '=1+1'];
        app(ReportWorkbookMetadataWriter::class)->append($book, '=Report', 'Dataset', $filters);
        $metadata = $book->getSheetByName('Metadata');
        self::assertNotNull($metadata);
        self::assertSame('=Report', $metadata->getCell('B1')->getValue());
        self::assertSame(DataType::TYPE_STRING, $metadata->getCell('B1')->getDataType());
        self::assertSame($filters, json_decode($metadata->getCell('B6')->getValue(), true, flags: JSON_THROW_ON_ERROR));
        self::assertSame(1, $book->getSheet(0)->getCell('B1')->getValue());
        self::assertSame(DataType::TYPE_NUMERIC, $book->getSheet(0)->getCell('B1')->getDataType());
        $book->disconnectWorksheets();
    }
}
