<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownSourceRowsQuery;
use App\Application\Reporting\UseCases\GetServicePackageProfitBreakdownHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServicePackageProfitTemporalIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_revision_does_not_restate_historical_package_breakdown(): void
    {
        DB::table('notes')->insert([
            'id' => 'package-note', 'customer_name' => 'Current Customer', 'transaction_date' => '2026-09-15',
            'total_rupiah' => 130001, 'current_revision_id' => 'package-note-r002', 'latest_revision_number' => 2,
            'created_at' => '2026-09-15 09:00:00', 'updated_at' => '2026-10-01 10:00:00',
        ]);
        DB::table('note_revisions')->insert([
            [
                'id' => 'package-note-r001', 'note_root_id' => 'package-note', 'revision_number' => 1,
                'parent_revision_id' => null, 'created_by_actor_id' => null, 'reason' => 'Initial',
                'customer_name' => 'Historical Customer', 'customer_phone' => null, 'transaction_date' => '2026-09-15',
                'grand_total_rupiah' => 100001, 'line_count' => 1, 'created_at' => '2026-09-15 10:00:00', 'updated_at' => null,
            ],
            [
                'id' => 'package-note-r002', 'note_root_id' => 'package-note', 'revision_number' => 2,
                'parent_revision_id' => 'package-note-r001', 'created_by_actor_id' => null, 'reason' => 'Future edit',
                'customer_name' => 'Current Customer', 'customer_phone' => null, 'transaction_date' => '2026-09-15',
                'grand_total_rupiah' => 130001, 'line_count' => 1, 'created_at' => '2026-10-01 10:00:00', 'updated_at' => null,
            ],
        ]);
        DB::table('note_revision_lines')->insert([
            [
                'id' => 'package-note-r001-line-01', 'note_revision_id' => 'package-note-r001', 'work_item_root_id' => 'package-old',
                'line_no' => 1, 'transaction_type' => 'service_with_store_stock_part', 'status' => 'active',
                'service_label' => 'Paket Lama', 'service_price_rupiah' => 30000, 'subtotal_rupiah' => 100001,
                'payload' => json_encode([
                    'store_stock_lines' => [['id' => 'package-old-part', 'product_id' => 'product-a', 'qty' => 1, 'line_total_rupiah' => 60001]],
                    'parts_total_rupiah' => 60001, 'service_price_rupiah' => 30000,
                    'package_profit_rupiah' => 10000, 'total_service_component_rupiah' => 40000,
                    'package_base_service_price_rupiah' => 30000, 'package_service_extra_rupiah' => 0,
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-09-15 10:00:00', 'updated_at' => null,
            ],
            [
                'id' => 'package-note-r002-line-01', 'note_revision_id' => 'package-note-r002', 'work_item_root_id' => 'package-new',
                'line_no' => 1, 'transaction_type' => 'service_with_store_stock_part', 'status' => 'active',
                'service_label' => 'Paket Baru', 'service_price_rupiah' => 40000, 'subtotal_rupiah' => 130001,
                'payload' => json_encode([
                    'store_stock_lines' => [['id' => 'package-new-part', 'product_id' => 'product-b', 'qty' => 1, 'line_total_rupiah' => 70001]],
                    'parts_total_rupiah' => 70001, 'service_price_rupiah' => 40000,
                    'package_profit_rupiah' => 20000, 'total_service_component_rupiah' => 60000,
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-10-01 10:00:00', 'updated_at' => null,
            ],
        ]);

        $data = app(GetServicePackageProfitBreakdownHandler::class)->handle('2026-09-01', '2026-09-30')->data();
        self::assertCount(1, $data['rows']);
        self::assertSame('Historical Customer', $data['rows'][0]['customer_name']);
        self::assertSame(100001, $data['rows'][0]['package_sold_amount_rupiah']);
        self::assertSame(60001, $data['rows'][0]['parts_total_rupiah']);
        self::assertSame(30000, $data['rows'][0]['service_price_rupiah']);
        self::assertSame(10000, $data['rows'][0]['package_profit_rupiah']);
        self::assertSame(70001, $data['rows'][0]['total_package_gross_profit_rupiah']);
    }

    public function test_future_created_backdated_unversioned_package_is_excluded_from_historical_legacy_fallback(): void
    {
        DB::table('notes')->insert([
            'id' => 'future-package-note',
            'customer_name' => 'Future Package Customer',
            'transaction_date' => '2026-09-15',
            'total_rupiah' => 100001,
            'created_at' => '2026-10-01 08:00:00',
            'updated_at' => '2026-10-01 08:00:00',
        ]);
        DB::table('work_items')->insert([
            'id' => 'future-package-work-item',
            'note_id' => 'future-package-note',
            'line_no' => 1,
            'transaction_type' => 'service_with_store_stock_part',
            'status' => 'open',
            'subtotal_rupiah' => 100001,
        ]);
        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'future-package-work-item',
            'service_name' => 'Future Package',
            'service_price_rupiah' => 100001,
            'part_source' => 'store_stock',
        ]);

        $historicalRows = app(BreakdownSourceRowsQuery::class)->rows(
            '2026-09-01',
            '2026-09-30',
            '2026-09-30',
            true,
        );

        self::assertCount(0, $historicalRows);
    }
}
