<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServicePackageProfitPdfFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_print_same_period_and_reject_oversized_range(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->get('/admin/reports/service-package-profit-breakdown/export.pdf?period_mode=monthly&reference_date=2026-09-01')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/admin/reports/service-package-profit-breakdown/export.pdf?period_mode=custom&date_from=2026-09-01&date_to=2026-11-01')
            ->assertStatus(422);
    }

    public function test_cashier_cannot_export(): void
    {
        $this->loginAsKasir();
        $this->get('/admin/reports/service-package-profit-breakdown/export.pdf')->assertRedirect(route('cashier.dashboard'));
    }
}
