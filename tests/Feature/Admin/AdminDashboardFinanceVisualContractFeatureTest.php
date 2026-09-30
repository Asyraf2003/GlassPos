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

    public function test_finance_visual_surfaces_are_present_even_without_period_data(): void
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
        $response->assertSee('data-finance-chart="bar"', false);
        $response->assertSee('data-finance-chart="donut"', false);
        $response->assertSee('dashboard-chart-shell', false);
        $response->assertSee('dashboard-finance.js', false);
        $response->assertSee('admin-chart-operational-performance', false);

        self::assertDoesNotMatchRegularExpression(
            '/data-finance-chart="(?:bar|donut)"[^>]*\shidden(?:\s|>)/',
            $response->getContent(),
        );
    }
}
