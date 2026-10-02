<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminDashboardFinanceVisualContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_visual_surfaces_remain_visible_without_fake_zero_charts(): void
    {
        $user = User::query()->create([
            'name' => 'Dashboard Visual Admin',
            'email' => 'dashboard-visual@example.test',
            'password' => 'password123',
        ]);
        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('dashboard-chart-shell', false);
        $response->assertSee('dashboard-chart-empty', false);
        $response->assertSee('Belum ada biaya operasional pada periode ini.');
        $response->assertSee('Belum ada biaya atau pencairan gaji pada periode ini.');
        $response->assertDontSee('data-finance-chart=', false);
        $response->assertSee('dashboard-finance.js', false);
        $response->assertSee('admin-chart-operational-performance', false);
    }
}
