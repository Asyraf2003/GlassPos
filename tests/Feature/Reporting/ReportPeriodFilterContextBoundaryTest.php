<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class ReportPeriodFilterContextBoundaryTest extends TestCase
{
    public function test_period_filter_default_keeps_explicit_date_range(): void
    {
        $html = $this->renderPeriodFilter([
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
            'period_mode' => 'monthly',
        ]);

        self::assertStringContainsString('Rentang Aktif', $html);
        self::assertStringContainsString('01 Januari 2030 s/d 31 Januari 2030', $html);
        self::assertStringNotContainsString('Bulan Terkait', $html);
    }

    public function test_period_filter_context_opt_in_uses_month_label_for_same_month(): void
    {
        $html = $this->renderPeriodFilter([
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
            'period_mode' => 'monthly',
        ], true);

        self::assertStringContainsString('Bulan Terkait', $html);
        self::assertStringContainsString('Januari 2030', $html);
        self::assertStringNotContainsString('01 Januari 2030 s/d 31 Januari 2030', $html);
    }

    public function test_period_filter_context_opt_in_keeps_date_range_for_cross_month(): void
    {
        $html = $this->renderPeriodFilter([
            'date_from' => '2030-01-31',
            'date_to' => '2030-02-01',
            'period_mode' => 'custom',
        ], true);

        self::assertStringContainsString('Rentang Tanggal', $html);
        self::assertStringContainsString('31 Januari 2030 s/d 01 Februari 2030', $html);
        self::assertStringNotContainsString('Bulan Terkait', $html);
    }

    /**
     * @param array<string, string> $filters
     */
    private function renderPeriodFilter(array $filters, bool $useContext = false): string
    {
        return view('admin.reporting.partials.period_filter', [
            'formId' => 'report-period-boundary-filter-form',
            'action' => '/reports/boundary',
            'resetUrl' => '/reports/boundary',
            'basisDateLabel' => 'Tanggal referensi laporan',
            'filters' => $filters,
            'useReportPeriodContext' => $useContext,
        ])->render();
    }
}
