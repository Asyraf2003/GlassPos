<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use App\Application\Procurement\UseCases\UpdateSupplierInvoiceHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class SupplierInvoiceNumberCheckFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_check_reuses_utf8_trim_lowercase_canonical_rule_and_voided_reuse(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban', 'Federal', 100, 15000);
        $id = $this->create('INV-ÄBC');

        $this->getJson($this->checkUrl(' inv-äbc '))->assertOk()->assertJsonPath('data.duplicate', true)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson($this->checkUrl('INV-UNIQUE'))->assertOk()->assertJsonPath('data.duplicate', false);
        DB::table('supplier_invoices')->where('id', $id)->update(['voided_at' => '2026-03-15 10:00:00']);
        $this->getJson($this->checkUrl(' inv-äbc '))->assertOk()->assertJsonPath('data.duplicate', false);
        $this->create('inv-äbc');
        $this->assertDatabaseCount('supplier_invoices', 2);
    }

    public function test_backend_rejects_duplicate_without_frontend_or_request_validator_and_rolls_back(): void
    {
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban', 'Federal', 100, 15000);
        $this->create('INV-DUPLICATE');
        $result = app(CreateSupplierInvoiceFlowHandler::class)->handle(
            ' inv-duplicate ', 'PT Other Supplier', '2026-03-12', $this->lines(), false,
        );
        $this->assertTrue($result->isFailure());
        $this->assertSame(['supplier_invoice' => ['SUPPLIER_INVOICE_DUPLICATE_NUMBER']], $result->errors());
        $this->assertDatabaseCount('supplier_invoices', 1);
        $this->assertDatabaseCount('supplier_invoice_versions', 1);
        $this->assertDatabaseCount('supplier_invoice_lines', 1);
        $this->assertDatabaseCount('suppliers', 1);
    }

    public function test_backend_constraint_also_rejects_duplicate_revision_without_request_validation(): void
    {
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban', 'Federal', 100, 15000);
        $this->create('INV-ONE');
        $id = $this->create('INV-TWO');
        $oldVersion = DB::table('supplier_invoice_versions')->where('supplier_invoice_id', $id)->first();
        $result = app(UpdateSupplierInvoiceHandler::class)->handle(
            $id, ' inv-one ', 'PT Supplier', '2026-03-12', $this->lines(), 1, 'Duplicate revision',
        );
        $this->assertTrue($result->isFailure());
        $this->assertSame(['supplier_invoice' => ['SUPPLIER_INVOICE_DUPLICATE_NUMBER']], $result->errors());
        $this->assertDatabaseHas('supplier_invoices', ['id' => $id, 'nomor_faktur' => 'INV-TWO', 'last_revision_no' => 1]);
        $this->assertDatabaseCount('supplier_invoice_versions', 2);
        $this->assertEquals($oldVersion, DB::table('supplier_invoice_versions')->where('id', $oldVersion->id)->first());
    }

    public function test_check_endpoint_is_read_only_and_protected_by_admin_access(): void
    {
        $this->get($this->checkUrl('INV-ONE'))->assertRedirect(route('login'));
        $this->loginAsKasir();
        $this->get($this->checkUrl('INV-ONE'))->assertRedirect(route('cashier.dashboard'));
        $this->assertDatabaseCount('supplier_invoices', 0);
    }

    public function test_invalid_check_input_returns_validation_errors(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->getJson($this->checkUrl(''))->assertUnprocessable()->assertJsonValidationErrors('nomor_faktur');
        $this->getJson(route('admin.procurement.supplier-invoices.check-number', ['nomor_faktur' => ['bad']]))
            ->assertUnprocessable()->assertJsonValidationErrors('nomor_faktur');
    }

    public function test_create_page_wires_inline_feedback_script_endpoint_and_server_error_disabled_submit(): void
    {
        $this->loginAsAuthorizedAdmin();
        $this->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag([
            'nomor_faktur' => ['Nomor faktur sudah dipakai oleh nota supplier aktif.'],
        ]))])->get(route('admin.procurement.supplier-invoices.create'))->assertOk()
            ->assertSee('data-invoice-number-feedback', false)
            ->assertSee('aria-describedby="invoice-number-feedback"', false)
            ->assertSee('supplier-invoice-number-validation.js', false)
            ->assertSee('invoiceNumberCheckEndpoint', false)
            ->assertSee('type="submit" class="btn btn-primary" disabled', false);
    }

    private function checkUrl(string $number): string
    {
        return route('admin.procurement.supplier-invoices.check-number', ['nomor_faktur' => $number]);
    }

    private function lines(): array
    {
        return [['line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 20000]];
    }

    private function create(string $number): string
    {
        $result = app(CreateSupplierInvoiceFlowHandler::class)->handle($number, 'PT Supplier', '2026-03-12', $this->lines(), false);
        $this->assertFalse($result->isFailure(), $result->message() ?? 'Create failed');

        return (string) $result->data()['id'];
    }
}
