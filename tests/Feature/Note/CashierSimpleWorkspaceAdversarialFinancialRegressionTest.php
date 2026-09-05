<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CashierSimpleWorkspaceAdversarialFinancialRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_create_without_items_cannot_mutate_transaction_or_finance_state(): void
    {
        $this->loginAsKasir();
        $payload = $this->payload('adversarial-empty', $this->serviceItem());
        $payload['items'] = [];

        $response = $this->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), $payload);

        $response->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors(['items']);
        $this->assertNoBusinessMutation();
    }

    public function test_product_query_text_without_authoritative_selected_id_is_rejected(): void
    {
        $this->loginAsKasir();
        $item = $this->productItem('', 1, 50000);
        $item['product_lines'][0]['selected_label'] = 'Produk ketikan tanpa pilihan';

        $response = $this->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), $this->payload('adversarial-product-text', $item));

        $response->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors(['items.0.product_lines.0.product_id']);
        $this->assertNoBusinessMutation();
    }

    public function test_selected_product_id_remains_authoritative_when_untrusted_display_label_changes(): void
    {
        $this->loginAsKasir();
        $this->seedProduct('product-authoritative-a', 'AUTH-A', 'Produk Authority A', 50000, 5, 30000);
        $this->seedProduct('product-authoritative-b', 'AUTH-B', 'Produk Authority B', 90000, 7, 40000);
        $item = $this->productItem('product-authoritative-a', 2, 50000);
        $item['product_lines'][0]['selected_label'] = 'Produk Authority B · label spoof';

        $response = $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-product-authority', $item),
        );

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'product_id' => 'product-authoritative-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);
        $this->assertDatabaseMissing('work_item_store_stock_lines', [
            'product_id' => 'product-authoritative-b',
        ]);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-authoritative-a',
            'qty_on_hand' => 3,
        ]);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-authoritative-b',
            'qty_on_hand' => 7,
        ]);
    }

    public function test_submit_rechecks_stock_after_lookup_and_rolls_back_atomically(): void
    {
        $this->loginAsKasir();
        $this->seedProduct('product-stale-stock', 'STALE-1', 'Produk Stok Berubah', 50000, 1, 30000);

        $response = $this->from(route('cashier.notes.workspace.create'))->post(
            route('notes.workspace.store'),
            $this->payload(
                'adversarial-stale-stock',
                $this->productItem('product-stale-stock', 2, 50000),
                $this->fullCash(100000),
            ),
        );

        $response->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors(['workspace']);
        $this->assertNoBusinessMutation();
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-stale-stock',
            'qty_on_hand' => 1,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_simple_payload_cannot_bypass_current_product_floor_price(): void
    {
        $this->loginAsKasir();
        $this->seedProduct('product-floor', 'FLOOR-1', 'Produk Harga Minimum', 50000, 5, 30000);

        $response = $this->from(route('cashier.notes.workspace.create'))->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-floor', $this->productItem('product-floor', 1, 49999)),
        );

        $response->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors([
                'workspace' => 'Harga jual pada store stock line tidak boleh di bawah harga jual minimum.',
            ]);
        $this->assertNoBusinessMutation();
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-floor',
            'qty_on_hand' => 5,
        ]);
    }

    public function test_hidden_simple_defaults_persist_as_structured_note_fields(): void
    {
        $this->loginAsKasir();
        $payload = $this->payload('adversarial-hidden-defaults', $this->serviceItem(45000));
        $payload['note'] = [
            'customer_name' => 'Pelanggan baru',
            'customer_phone' => '',
            'transaction_date' => '2026-09-04',
            'operational_note' => '',
        ];

        $response = $this->post(route('notes.workspace.store'), $payload);

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('notes', [
            'customer_name' => 'Pelanggan baru',
            'customer_phone' => null,
            'transaction_date' => '2026-09-04',
            'operational_note' => null,
            'total_rupiah' => 45000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'service_name' => 'Servis Adversarial',
            'service_price_rupiah' => 45000,
        ]);
    }

    #[DataProvider('invalidPartialAmounts')]
    public function test_invalid_partial_boundary_fails_before_any_mutation(int $amount): void
    {
        $this->loginAsKasir();
        $payment = [
            'decision' => 'pay_partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-09-04',
            'amount_paid_rupiah' => $amount,
            'amount_received_rupiah' => max($amount, 1),
        ];

        $response = $this->from(route('cashier.notes.workspace.create'))->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-partial-'.$amount, $this->serviceItem(), $payment),
        );

        $response->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors(['inline_payment.amount_paid_rupiah']);
        $this->assertNoBusinessMutation();
    }

    /** @return array<string, array{int}> */
    public static function invalidPartialAmounts(): array
    {
        return [
            'zero' => [0],
            'greater than payable' => [120000],
        ];
    }

    public function test_partial_amount_equal_to_payable_is_a_valid_settlement(): void
    {
        $this->loginAsKasir();
        $payment = [
            'decision' => 'pay_partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-09-04',
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 100000,
        ];

        $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-partial-equal', $this->serviceItem(), $payment),
        )->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('customer_payments', ['amount_rupiah' => 100000]);
        $this->assertDatabaseHas('note_history_projection', ['outstanding_rupiah' => 0]);
    }

    public function test_detail_transfer_full_payment_keeps_cash_detail_empty(): void
    {
        $this->loginAsKasir();
        $payment = [
            'decision' => 'pay_full',
            'payment_method' => 'transfer',
            'paid_at' => '2026-09-04',
        ];

        $response = $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-transfer', $this->serviceItem(), $payment),
        );

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 100000,
            'payment_method' => 'transfer',
            'paid_at' => '2026-09-04',
        ]);
        $this->assertDatabaseCount('customer_payment_cash_details', 0);
        $this->assertDatabaseHas('note_history_projection', [
            'note_state' => 'closed',
            'net_paid_rupiah' => 100000,
            'outstanding_rupiah' => 0,
        ]);
    }

    public function test_save_note_skip_has_no_payment_or_cash_ledger_side_effect(): void
    {
        $this->loginAsKasir();

        $response = $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-skip', $this->serviceItem()),
        );

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('customer_payment_cash_details', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
        $this->assertDatabaseHas('note_history_projection', [
            'note_state' => 'open',
            'net_paid_rupiah' => 0,
            'outstanding_rupiah' => 100000,
        ]);
    }

    public function test_full_cash_exact_closes_note_without_change(): void
    {
        $this->loginAsKasir();

        $response = $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-full-cash', $this->serviceItem(), $this->fullCash(100000)),
        );

        $response->assertRedirect(route('cashier.notes.index'));
        $paymentId = (string) DB::table('customer_payments')->value('id');
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 100000,
            'change_rupiah' => 0,
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_state' => 'closed',
            'net_paid_rupiah' => 100000,
            'outstanding_rupiah' => 0,
        ]);
    }

    public function test_partial_cash_records_precise_paid_and_outstanding_amounts(): void
    {
        $this->loginAsKasir();
        $payment = [
            'decision' => 'pay_partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-09-04',
            'amount_paid_rupiah' => 35000,
            'amount_received_rupiah' => 35000,
        ];

        $response = $this->post(
            route('notes.workspace.store'),
            $this->payload('adversarial-partial-valid', $this->serviceItem(), $payment),
        );

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 35000,
            'payment_method' => 'cash',
        ]);
        $this->assertSame(35000, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('note_history_projection', [
            'note_state' => 'open',
            'net_paid_rupiah' => 35000,
            'outstanding_rupiah' => 65000,
        ]);
    }

    public function test_same_idempotency_key_and_payload_replays_without_duplicate_financial_mutation(): void
    {
        $user = $this->loginAsKasir();
        $payload = $this->payload(
            'adversarial-idempotent-replay',
            $this->serviceItem(),
            $this->fullCash(100000),
        );

        $this->post(route('notes.workspace.store'), $payload)
            ->assertRedirect(route('cashier.notes.index'));
        $this->actingAs($user)->post(route('notes.workspace.store'), $payload)
            ->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseCount('notes', 1);
        $this->assertDatabaseCount('work_items', 1);
        $this->assertDatabaseCount('customer_payments', 1);
        $this->assertDatabaseCount('customer_payment_cash_details', 1);
        $this->assertDatabaseCount('payment_component_allocations', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
    }

    public function test_same_idempotency_key_with_changed_payload_fails_closed(): void
    {
        $this->loginAsKasir();
        $payload = $this->payload(
            'adversarial-idempotent-conflict',
            $this->serviceItem(),
            $this->fullCash(100000),
        );
        $changed = $payload;
        $changed['note']['customer_name'] = 'Payload berbeda';

        $this->post(route('notes.workspace.store'), $payload)
            ->assertRedirect(route('cashier.notes.index'));
        $this->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), $changed)
            ->assertRedirect(route('cashier.notes.workspace.create'))
            ->assertSessionHasErrors(['workspace']);

        $this->assertDatabaseCount('notes', 1);
        $this->assertDatabaseCount('customer_payments', 1);
        $this->assertDatabaseCount('payment_component_allocations', 1);
        $this->assertDatabaseMissing('notes', ['customer_name' => 'Payload berbeda']);
    }

    /** @return array<string, mixed> */
    private function payload(string $key, array $item, ?array $payment = null): array
    {
        return [
            'idempotency_key' => $key,
            'note' => [
                'customer_name' => 'Customer '.$key,
                'customer_phone' => '',
                'transaction_date' => '2026-09-04',
                'operational_note' => '',
            ],
            'items' => [$item],
            'inline_payment' => $payment ?? [
                'decision' => 'skip',
                'payment_method' => '',
                'paid_at' => '2026-09-04',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function serviceItem(int $price = 100000): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'pricing_mode' => 'manual_split',
            'service' => [
                'name' => 'Servis Adversarial',
                'price_rupiah' => $price,
                'notes' => '',
            ],
            'product_lines' => [],
            'external_purchase_lines' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function productItem(string $productId, int $qty, int $unitPrice): array
    {
        return [
            'entry_mode' => 'product',
            'part_source' => 'none',
            'product_lines' => [[
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price_rupiah' => $unitPrice,
                'price_basis' => 'current_catalog',
            ]],
            'service' => [],
            'external_purchase_lines' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function fullCash(int $received): array
    {
        return [
            'decision' => 'pay_full',
            'payment_method' => 'cash',
            'paid_at' => '2026-09-04',
            'amount_received_rupiah' => $received,
        ];
    }

    private function seedProduct(
        string $id,
        string $code,
        string $name,
        int $salePrice,
        int $stock,
        int $averageCost,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $code,
            'nama_barang' => $name,
            'merek' => 'Adversarial',
            'ukuran' => null,
            'harga_jual' => $salePrice,
        ]);
        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => $stock,
        ]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => $id,
            'avg_cost_rupiah' => $averageCost,
            'inventory_value_rupiah' => $stock * $averageCost,
        ]);
    }

    private function assertNoBusinessMutation(): void
    {
        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('work_item_external_purchase_lines', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('customer_payment_cash_details', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('note_history_projection', 0);
        $this->assertDatabaseCount('idempotency_records', 0);
    }
}
