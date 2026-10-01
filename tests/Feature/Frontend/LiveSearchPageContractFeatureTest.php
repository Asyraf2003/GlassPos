<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LiveSearchPageContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_pages_load_the_gate_before_page_scripts_and_export_real_blade_for_chromium(): void
    {
        $this->loginAsAuthorizedAdmin();
        $pages = [
            'suppliers' => ['admin.suppliers.index', 'admin-suppliers-table.js'],
            'products' => ['admin.products.index', 'admin-products-table.js'],
            'procurement' => ['admin.procurement.supplier-invoices.index', 'admin-procurement-invoices-table.js'],
            'expenses' => ['admin.expenses.index', 'admin-expenses-table.js'],
            'services' => ['admin.services.index', 'admin-services-table.js'],
            'packages' => ['admin.service-product-templates.index', 'admin-service-product-templates-table.js'],
            'employees' => ['admin.employees.index', 'admin-employees-table.js'],
            'payrolls' => ['admin.payrolls.index', 'admin-payrolls-table.js'],
            'debts' => ['admin.employee-debts.index', 'admin-employee-debts-table.js'],
            'audit' => ['admin.audit-logs.index', 'admin-audit-logs-table.js'],
            'notes' => ['admin.notes.index', 'admin-note-index.js'],
            'categories' => ['admin.expenses.categories.index', 'admin-expense-categories-table.js'],
            'procurement-create' => ['admin.procurement.supplier-invoices.create', 'admin-procurement-create.js'],
            'package-create' => ['admin.service-product-templates.create', 'admin-service-product-template.js'],
        ];
        $this->assertAndExport($pages);
        $this->loginAsKasir();
        $this->assertAndExport([
            'cashier-notes' => ['cashier.notes.index', 'cashier-note-index.js'],
            'cashier-products' => ['cashier.products.search', 'cashier-dashboard.js'],
            'workspace' => ['cashier.notes.workspace.create', 'cashier-note-workspace/search.js'],
        ]);
    }

    private function assertAndExport(array $pages): void
    {
        $directory = getenv('LIVE_SEARCH_EXPORT_DIR');
        foreach ($pages as $name => [$route, $script]) {
            $response = $this->get(route($route))->assertOk();
            $html = $response->getContent();
            $this->assertIsString($html);
            $response->assertSee('shared/live-search.js', false)->assertSee($script, false);
            $this->assertLessThan(strpos($html, $script), strpos($html, 'shared/live-search.js'));
            if (in_array($route, ['cashier.products.search', 'cashier.notes.workspace.create'], true)) {
                $this->assertStringContainsString('class="sidebar-item active"', $html);
            }
            if (is_string($directory) && is_dir($directory)) {
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
    }
}
