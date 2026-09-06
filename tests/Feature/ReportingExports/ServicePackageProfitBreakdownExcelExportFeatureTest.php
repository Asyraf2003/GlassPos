<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class ServicePackageProfitBreakdownExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_empty_service_package_profit_breakdown_as_xlsx(): void
    {
        $this->loginAsAuthorizedAdmin();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel', [
            'period_mode' => 'monthly',
            'reference_date' => '2030-01-31',
        ]));

        $response->assertOk();
        $response->assertDownload('laporan-laba-paket-service-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'service-package-profit-breakdown-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Detail Paket'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Paket');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);

        $this->assertSame('Laba Paket Service', $summary->getCell('A1')->getValue());
        $this->assertSame('Periode', $summary->getCell('A3')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B3')->getValue());
        $this->assertSame('Jumlah Paket', $summary->getCell('A4')->getValue());
        $this->assertSame(0, $summary->getCell('B4')->getValue());

        $this->assertSame('ID Nota', $detail->getCell('B1')->getValue());
        $this->assertSame('Laba Kotor Paket untuk Toko', $detail->getCell('Q1')->getValue());
        $this->assertNull($detail->getCell('B2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_service_package_profit_breakdown(): void
    {
        $this->loginAsKasir();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_service_package_profit_breakdown_excel_export_rejects_range_longer_than_366_days(): void
    {
        $this->loginAsAuthorizedAdmin();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel', [
            'period_mode' => 'custom',
            'date_from' => '2030-01-01',
            'date_to' => '2031-01-02',
        ]));

        $response->assertStatus(422);
        $response->assertSeeText('Export Excel maksimal 366 hari.');
    }
}
