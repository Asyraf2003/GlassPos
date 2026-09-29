<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use Tests\TestCase;

final class ReportingPdfDetailParityFeatureTest extends TestCase
{
    public function test_pdf_templates_render_supplied_detail_rows_without_recalculating_values(): void
    {
        foreach (['employee_debt', 'supplier_payable', 'payroll', 'operational_expense', 'transaction_summary', 'transaction_cash_ledger', 'inventory_stock_value', 'service_package_profit_breakdown'] as $report) {
            $html = view('admin.reporting.'.$report.'.export_pdf', [
                'title' => 'Report', 'periodLabel' => 'September', 'periodLabelCaption' => 'Periode',
                'referenceDateLabel' => '30 September', 'generatedAt' => 'now', 'summaryItems' => [],
                'detailTables' => [['title' => 'Rincian', 'columns' => ['id' => 'Identitas', 'value' => 'Sisa per 30-09-2026'],
                    'rows' => [['id' => 'detail-proof-'.$report, 'value' => 'Rp 800.001']]]],
            ])->render();
            self::assertStringContainsString('detail-proof-'.$report, $html, $report);
            self::assertStringContainsString('Rp 800.001', $html, $report);
            self::assertStringContainsString('Sisa per 30-09-2026', $html, $report);
        }
    }
}
