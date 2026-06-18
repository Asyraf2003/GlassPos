<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceProductTemplate;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminServiceProductTemplateNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_links_to_service_product_template_management(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.service-product-templates.index'));

        $response->assertOk();
        $response->assertSee('Template Jasa + Produk', false);
        $response->assertSee('admin.service-product-templates.*', false);
        $response->assertSee(route('admin.service-product-templates.index'), false);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Admin Navigation User',
            'email' => $role . '-service-product-template-navigation@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
