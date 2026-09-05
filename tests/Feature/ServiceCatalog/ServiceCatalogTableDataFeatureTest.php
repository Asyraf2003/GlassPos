<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceCatalogTableDataFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_services_with_relevance_and_filter_status(): void
    {
        $admin = $this->user('admin', 'service-table-admin@example.test');
        $this->service('service-relevance-prefix', 'Ganti Oli Mesin', 75_000, true, '2026-01-01 09:00:00');
        $this->service('service-relevance-exact', 'Oli', 50_000, true, '2026-01-02 09:00:00');
        $this->service('service-relevance-inactive', 'Oli Gardan', 40_000, false, '2026-01-03 09:00:00');

        $response = $this->actingAs($admin)->getJson(route('admin.services.table', [
            'q' => 'oli',
            'status' => 'active',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.rows.0.id', 'service-relevance-exact')
            ->assertJsonPath('data.rows.1.id', 'service-relevance-prefix');
    }

    public function test_explicit_price_sort_happens_before_pagination(): void
    {
        $admin = $this->user('admin', 'service-table-sort@example.test');

        DB::table('service_product_template_lines')->delete();
        DB::table('service_product_templates')->delete();
        DB::table('service_catalog_items')->delete();

        for ($index = 1; $index <= 11; $index++) {
            $this->service(
                sprintf('service-sort-%02d', $index),
                sprintf('Jasa %02d', $index),
                $index * 1_000,
                true,
                sprintf('2026-01-%02d 09:00:00', $index),
            );
        }

        $response = $this->actingAs($admin)->getJson(route('admin.services.table', [
            'sort_by' => 'default_price_rupiah',
            'sort_dir' => 'desc',
            'page' => 2,
            'per_page' => 10,
            'status' => 'all',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 11)
            ->assertJsonPath('data.rows.0.default_price_rupiah', 1_000);
    }

    public function test_default_order_is_active_first_then_name_with_stable_identity_fallback(): void
    {
        $admin = $this->user('admin', 'service-table-order@example.test');

        DB::table('service_product_template_lines')->delete();
        DB::table('service_product_templates')->delete();
        DB::table('service_catalog_items')->delete();
        $this->service('service-inactive-new', 'Nonaktif Baru', 10_000, false, '2026-01-03 09:00:00');
        $this->service('service-active-old', 'Aktif Lama', 20_000, true, '2026-01-01 09:00:00');
        $this->service('service-active-new', 'Aktif Baru', 30_000, true, '2026-01-02 09:00:00');

        $response = $this->actingAs($admin)->getJson(route('admin.services.table', [
            'status' => 'all',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $this->assertSame(
            ['service-active-new', 'service-active-old', 'service-inactive-new'],
            collect($response->json('data.rows'))->pluck('id')->all(),
        );
    }

    public function test_table_payload_contains_existing_row_action_urls(): void
    {
        $admin = $this->user('admin', 'service-table-action@example.test');
        $this->service('service-actions', 'Tune Up', 100_000, true, '2026-01-01 09:00:00');

        $response = $this->actingAs($admin)->getJson(route('admin.services.table', ['q' => 'Tune Up']));

        $response->assertOk()
            ->assertJsonPath('data.rows.0.actions.edit_url', route('admin.services.edit', ['serviceId' => 'service-actions']))
            ->assertJsonPath('data.rows.0.actions.activate_url', route('admin.services.activate', ['serviceId' => 'service-actions']))
            ->assertJsonPath('data.rows.0.actions.deactivate_url', route('admin.services.deactivate', ['serviceId' => 'service-actions']));
    }

    public function test_table_request_rejects_invalid_sort_and_page_size(): void
    {
        $admin = $this->user('admin', 'service-table-validation@example.test');

        $this->actingAs($admin)->getJson(route('admin.services.table', [
            'sort_by' => 'unknown',
            'per_page' => 20,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['sort_by', 'per_page']);
    }

    public function test_guest_and_cashier_cannot_access_service_table_data(): void
    {
        $this->getJson(route('admin.services.table'))->assertUnauthorized();

        $cashier = $this->user('kasir', 'service-table-cashier@example.test');
        $this->actingAs($cashier)->getJson(route('admin.services.table'))
            ->assertRedirect(route('cashier.dashboard'));
    }

    private function service(
        string $id,
        string $name,
        int $price,
        bool $active,
        string $createdAt,
    ): void {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'default_price_rupiah' => $price,
            'is_active' => $active,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function user(string $role, string $email): User
    {
        $user = User::query()->create([
            'name' => 'Service Table User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
