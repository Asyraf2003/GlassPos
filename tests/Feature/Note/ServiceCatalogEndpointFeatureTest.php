<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceCatalogEndpointFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_lookup_service_by_plain_variant(): void
    {
        $this->loginAsKasir();

        $this->getJson(route('cashier.notes.services.lookup', ['q' => 'sok kopling besar']))
            ->assertOk()
            ->assertJsonPath('data.rows.0.label', 'Sok Kopling (Besar)')
            ->assertJsonPath('data.rows.0.default_price_rupiah', 120000);
    }

    public function test_cashier_can_lookup_service_by_partial_words(): void
    {
        $this->loginAsKasir();

        $this->getJson(route('cashier.notes.services.lookup', ['q' => 'sok besar']))
            ->assertOk()
            ->assertJsonPath('data.rows.0.label', 'Sok Kopling (Besar)');
    }

    public function test_cashier_can_open_service_lookup_without_query(): void
    {
        $this->loginAsKasir();

        $this->getJson(route('cashier.notes.services.lookup'))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Setting In (Kecil)']);
    }

    public function test_cashier_create_if_missing_does_not_update_existing_default_price(): void
    {
        $this->loginAsKasir();

        $this->postJson(route('cashier.notes.services.store'), [
            'name' => 'sok kopling besar',
            'default_price_rupiah' => 999000,
        ])->assertOk()->assertJsonPath('data.row.default_price_rupiah', 120000);

        $this->assertDatabaseHas('service_catalog_items', [
            'normalized_name' => 'sok kopling besar',
            'default_price_rupiah' => 120000,
        ]);
    }

    public function test_cashier_can_create_new_service_catalog_item(): void
    {
        $this->loginAsKasir();

        $this->postJson(route('cashier.notes.services.store'), [
            'name' => 'Skir Klep Baru',
            'default_price_rupiah' => '95.000',
        ])->assertOk()->assertJsonPath('data.row.default_price_rupiah', 95000);

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Skir Klep Baru',
            'normalized_name' => 'skir klep baru',
            'default_price_rupiah' => 95000,
        ]);
    }

    private function seedService(string $id, string $name, string $normalized, int $price): void
    {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => $normalized,
            'default_price_rupiah' => $price,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
