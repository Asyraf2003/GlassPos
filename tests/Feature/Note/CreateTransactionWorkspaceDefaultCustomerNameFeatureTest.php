<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceDefaultCustomerNameFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_create_does_not_expose_global_note_count_as_default_customer_name(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Nama Default',
            'email' => 'default-customer@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $storeResponse = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis A',
                    'price_rupiah' => 50000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $storeResponse->assertRedirect(route('cashier.notes.index'));

        $createResponse = $this->actingAs($user)->get(route('cashier.notes.workspace.create'));

        $createResponse->assertOk();
        $createResponse->assertDontSee('Pelanggan no 2', false);
        $createResponse->assertSee('value="Pelanggan baru"', false);
        $createResponse->assertSee('placeholder="Contoh: Pelanggan baru"', false);
    }
}
