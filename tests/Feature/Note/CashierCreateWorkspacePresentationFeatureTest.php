<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashierCreateWorkspacePresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_create_workspace_defaults_to_detail_but_keeps_simple_toggle_available(): void
    {
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?0'])
            ->get(route('cashier.notes.workspace.create'));

        $response->assertOk()
            ->assertSee('data-device-class="desktop"', false)
            ->assertSee('data-presentation-mode="detail"', false)
            ->assertSee('data-workspace-mode="create"', false)
            ->assertSee('data-detail-toggle', false)
            ->assertSee('id="workspace-detail-toggle"', false)
            ->assertSee('checked', false)
            ->assertSee('data-add-item-type="product"', false)
            ->assertSee('data-add-item-type="service"', false)
            ->assertSee('data-add-item-type="service_store_stock"', false)
            ->assertSee('data-add-item-type="service_external"', false);
    }

    public function test_handset_create_workspace_defaults_to_simple_with_same_canonical_form_contract(): void
    {
        $this->loginAsKasir();

        $response = $this->withHeaders(['Sec-CH-UA-Mobile' => '?1'])
            ->get(route('cashier.notes.workspace.create'));

        $response->assertOk()
            ->assertSee('data-device-class="handset"', false)
            ->assertSee('data-presentation-mode="simple"', false)
            ->assertSee('data-detail-toggle', false)
            ->assertSee('Simpan Nota')
            ->assertSee('Bayar Sebagian')
            ->assertSee('Bayar Penuh')
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('name="note[customer_name]"', false)
            ->assertSee('name="note[transaction_date]"', false)
            ->assertSee('name="inline_payment[decision]"', false);

        $html = (string) $response->getContent();
        self::assertSame(1, substr_count($html, 'name="inline_payment[decision]"'));
        self::assertSame(1, substr_count($html, 'name="idempotency_key"'));
    }

    public function test_workspace_assets_encode_device_aware_presentation_without_new_financial_path(): void
    {
        $presentation = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/presentation.js'));
        $payment = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/payment-flow.js'));
        $css = (string) file_get_contents(public_path('assets/static/css/cashier-note-workspace.css'));

        self::assertStringContainsString('root.dataset.presentationMode', $presentation);
        self::assertStringContainsString('detailToggle.checked ? "detail" : "simple"', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment?.("skip")', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment?.("full")', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment?.("partial", amount)', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment = (action, partial = 0) =>', $payment);
        self::assertStringContainsString('inline_payment_method_hidden', $payment);
        self::assertStringContainsString('[data-device-class="handset"]', $css);
        self::assertStringContainsString('[data-device-class="desktop"]', $css);
    }
}
