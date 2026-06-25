<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest extends TestCase
{
    public function test_workspace_js_tracks_package_selection_and_blocks_invalid_package_payload(): void
    {
        $packageSearchJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/package-search.js'));
        $paymentFlowJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/payment-flow.js'));
        $rowsJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/rows.js'));
        $draftJs = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/draft.js'));
        $blade = (string) file_get_contents(resource_path('views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php'));

        $this->assertStringContainsString('NS.applyPackageTemplate', $packageSearchJs);
        $this->assertStringContainsString('product_lines.slice(0, 3)', $packageSearchJs);
        $this->assertStringContainsString('serviceProductTemplateApplied', $packageSearchJs);
        $this->assertStringContainsString('[data-package-search]', $packageSearchJs);
        $this->assertStringContainsString('setValue(row, "[data-requires-service-product-template]", "1");', $packageSearchJs);

        $this->assertStringContainsString('serviceTotal <= 0', $paymentFlowJs);
        $this->assertStringContainsString('Harga servis wajib diisi sebelum proses nota.', $paymentFlowJs);
        $this->assertStringContainsString('Paket servis maksimal memakai 3 produk.', $paymentFlowJs);
        $this->assertStringContainsString('requiresServiceProductTemplate', $paymentFlowJs);
        $this->assertStringContainsString('Paket servis + produk wajib memakai template aktif.', $paymentFlowJs);
        $this->assertStringContainsString('[data-package-search]', $paymentFlowJs);

        $this->assertStringContainsString('[data-package-search]', $rowsJs);
        $this->assertStringContainsString('[data-requires-service-product-template]', $rowsJs);
        $this->assertStringContainsString('historical_package_snapshot', $rowsJs);
        $this->assertStringContainsString('packageLabelFromRow', $draftJs);

        $this->assertStringContainsString('requires_service_product_template', $blade);
        $this->assertStringContainsString('data-package-search', $blade);
        $this->assertStringContainsString('data-package-selected-section', $blade);
        $this->assertStringContainsString('data-service-price-raw', $blade);
        $this->assertStringContainsString('data-product-lines', $blade);
        $this->assertStringNotContainsString('data-add-product-line', $blade);
        $this->assertStringNotContainsString('Nama Paket/Jasa dari Template', $blade);
        $this->assertStringNotContainsString('Tambah Produk Opsional', $blade);
        $this->assertStringNotContainsString('Sok Kopling Besar', $blade);
    }
}
