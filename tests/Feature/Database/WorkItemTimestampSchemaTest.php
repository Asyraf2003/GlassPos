<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WorkItemTimestampSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_system_timestamps_on_work_item_tables(): void
    {
        $columns = [
            ['work_items', 'created_at'],
            ['work_items', 'updated_at'],
            ['work_item_service_details', 'created_at'],
            ['work_item_service_details', 'updated_at'],
            ['work_item_external_purchase_lines', 'created_at'],
            ['work_item_external_purchase_lines', 'updated_at'],
            ['work_item_store_stock_lines', 'created_at'],
            ['work_item_store_stock_lines', 'updated_at'],
        ];

        foreach ($columns as [$table, $column]) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Missing {$table}.{$column}",
            );
        }
    }
}
