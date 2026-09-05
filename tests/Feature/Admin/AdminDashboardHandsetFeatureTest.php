<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminDashboardHandsetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_handset_gets_supplier_hub_instead_of_full_dashboard(): void
    {
        $response = $this->actingAs($this->admin())
            ->withHeader('Sec-CH-UA-Mobile', '?1')
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-mobile-supplier-hub', false);
        $response->assertSee('Bayar Supplier');
        $response->assertSee('Cek Pembayaran Supplier');
        $response->assertDontSee('Total Nilai Nota Bulan Ini');
        $response->assertDontSee('admin-chart-operational-performance', false);
    }

    public function test_desktop_client_hint_keeps_existing_dashboard_surface(): void
    {
        $response = $this->actingAs($this->admin())
            ->withHeader('Sec-CH-UA-Mobile', '?0')
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ringkasan Toko');
        $response->assertDontSee('data-mobile-supplier-hub', false);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Handset Test',
            'email' => 'admin-handset@example.test',
            'password' => str_repeat('p', 12),
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
