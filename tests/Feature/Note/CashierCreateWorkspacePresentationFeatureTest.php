<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CashierCreateWorkspacePresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_workspace_defaults_to_simple_pos_with_four_direct_transaction_types(): void
    {
        $this->loginAsKasir();

        $response = $this->get(route('cashier.notes.workspace.create'));

        $response->assertOk()
            ->assertSee('data-presentation-mode="simple"', false)
            ->assertSee('data-workspace-mode="create"', false)
            ->assertSee('data-detail-toggle', false)
            ->assertSee('role="switch"', false)
            ->assertDontSee('data-mode-choice=', false)
            ->assertSee('data-add-item-type="product"', false)
            ->assertSee('data-add-item-type="service"', false)
            ->assertSee('data-add-item-type="service_store_stock"', false)
            ->assertSee('data-add-item-type="service_external"', false)
            ->assertSee('Simpan Nota')
            ->assertSee('Bayar Sebagian')
            ->assertSee('Bayar Penuh')
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('name="note[customer_name]"', false)
            ->assertSee('value="Pelanggan baru"', false)
            ->assertSee('name="note[transaction_date]"', false)
            ->assertSee('name="inline_payment[decision]"', false)
            ->assertSee('<div id="workspace-line-items" data-next-index="0"></div>', false)
            ->assertDontSee('Kasir · Nota Aktif')
            ->assertDontSee('Pilih transaksi, isi rincian, lalu simpan atau bayar langsung.')
            ->assertDontSee('workspace-type-icon', false)
            ->assertDontSee('workspace-empty-state', false);
    }

    public function test_workspace_assets_encode_shared_state_and_simple_payment_contract(): void
    {
        $presentation = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/presentation.js'));
        $payment = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/payment-flow.js'));
        $search = (string) file_get_contents(public_path('assets/static/js/pages/cashier-note-workspace/search.js'));
        $css = (string) file_get_contents(public_path('assets/static/css/cashier-note-workspace.css'));

        self::assertStringContainsString('NS.submitSimplePayment?.("skip")', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment?.("full")', $presentation);
        self::assertStringContainsString('NS.submitSimplePayment?.("partial", amount)', $presentation);
        self::assertStringContainsString('const simpleAvailable = root.dataset.workspaceMode === "create";', $presentation);
        self::assertStringContainsString('root.dataset.presentationMode = resolvedMode;', $presentation);
        self::assertStringContainsString('detailToggle.checked ? "detail" : "simple"', $presentation);
        self::assertStringContainsString('NS.syncSimpleActionAvailability', $presentation);

        self::assertStringContainsString('NS.submitSimplePayment = (action, partial = 0) =>', $payment);
        self::assertStringContainsString('applyMode("skip");', $payment);
        self::assertStringContainsString('applyMode(action === "partial" ? "partial" : "full");', $payment);
        self::assertStringContainsString('updateHidden("inline_payment_method_hidden", "cash");', $payment);
        self::assertStringContainsString('updateHidden("inline_payment_amount_received_rupiah", cashAmount);', $payment);
        self::assertStringContainsString('partial <= 0 || partial >= payable', $payment);

        self::assertStringContainsString('hidden.value = item.id;', $search);
        self::assertStringContainsString('search.value = "";', $search);
        self::assertStringContainsString('data-product-selected', $search);
        self::assertStringContainsString('event.key === "Escape"', $search);

        self::assertStringContainsString('@media (min-width: 992px)', $css);
        self::assertStringContainsString('grid-template-columns:', $css);
        self::assertStringContainsString('@media (max-width: 991.98px)', $css);
        self::assertStringContainsString('position: sticky;', $css);
    }
}
