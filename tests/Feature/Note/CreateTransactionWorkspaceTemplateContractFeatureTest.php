<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceTemplateContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_create_page_embeds_explicit_service_part_source_values(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Template',
            'email' => 'template-contract@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertSee('name="items[__INDEX__][part_source]" value="none"', false);
        $response->assertSee('name="items[__INDEX__][part_source]" value="store_stock"', false);
        $response->assertSee('name="items[__INDEX__][part_source]" value="external_purchase"', false);
    }

    public function test_workspace_create_page_embeds_service_store_stock_package_pricing_contract(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Contract',
            'email' => 'package-contract@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertSee('name="items[__INDEX__][pricing_mode]" value="package_auto_split"', false);
        $response->assertSee('data-pricing-mode', false);
        $response->assertSee('name="items[__INDEX__][package_total_rupiah]"', false);
        $response->assertSee('Total Paket', false);
        $response->assertSee('data-product-lines', false);
        $response->assertSee('data-product-line-template', false);
        $response->assertSee('__PRODUCT_INDEX__', false);
        $response->assertSee('data-add-product-line', false);
        $response->assertSee('data-remove-product-line', false);
    }

    public function test_workspace_create_page_embeds_service_catalog_contract(): void
    {
        $this->loginAsKasir();

        $response = $this->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertSee('serviceLookupEndpoint', false);
        $response->assertSee('serviceStoreEndpoint', false);
        $response->assertSee('service-catalog.js', false);
        $response->assertSee('data-service-name', false);
        $response->assertSee('data-service-results', false);
        $response->assertSee('data-service-default-fee-rupiah', false);
    }
}
