<?php

declare(strict_types=1);

namespace Tests\Feature\Seeder;

use Tests\TestCase;

final class ProductSeederIdempotencyFeatureTest extends TestCase
{
    public function test_active_basic_product_scenario_idempotency_is_pending_until_product_seeder_is_restored(): void
    {
        $this->markTestSkipped(
            'Product scenario seeders are pending restoration under database/seeders/Product.'
        );
    }

    public function test_recreated_product_scenario_idempotency_is_pending_until_product_seeder_is_restored(): void
    {
        $this->markTestSkipped(
            'Product scenario seeders are pending restoration under database/seeders/Product.'
        );
    }
}
