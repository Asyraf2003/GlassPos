<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Application\Reporting\Services\ReportTemporalContext;
use App\Ports\Out\ClockPort;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class ReportWorkbookMetadataWriter
{
    public function __construct(private readonly ClockPort $clock) {}

    public function append(Spreadsheet $book, string $report, string $source, array $filters): void
    {
        $sheet = $book->createSheet()->setTitle('Metadata');
        $rows = [
            ['Report', $report],
            ['Source dataset', $source],
            ['Date from', $filters['date_from'] ?? 'all'],
            ['Date to', $filters['date_to'] ?? 'all'],
            ['Generated at', $this->clock->now()->format('Y-m-d H:i:s P')],
            ['Filters', json_encode($filters, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ['Contract', ReportTemporalContext::description($report, $filters)],
        ];
        foreach ($rows as $index => $row) {
            $sheet->setCellValueExplicit('A'.($index + 1), $row[0], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.($index + 1), (string) $row[1], DataType::TYPE_STRING);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(90);
        $sheet->getStyle('B1:B7')->getAlignment()->setWrapText(true);
    }
}
