<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest extends TestCase
{
    public function test_workspace_js_tracks_template_default_service_price_and_blocks_lower_package_total(): void
    {
        $serviceCatalogJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/service-catalog.js'));
        $paymentFlowJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/payment-flow.js'));

        $this->assertStringContainsString('serviceTemplateDefaultPriceRupiah', $serviceCatalogJs);
        $this->assertStringContainsString('delete row.dataset.serviceTemplateDefaultPriceRupiah', $serviceCatalogJs);
        $this->assertStringContainsString('template.default_service_price_rupiah', $serviceCatalogJs);

        $this->assertStringContainsString('minimumTemplateServicePrice', $paymentFlowJs);
        $this->assertStringContainsString('sparepartTotal + minimumTemplateServicePrice', $paymentFlowJs);
        $this->assertStringContainsString('Total paket tidak boleh membuat harga jasa di bawah default template', $paymentFlowJs);
    }
}
