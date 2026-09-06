<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Out\Reporting;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownRowMapper;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ServicePackageProfitBreakdownRowMapperTest extends TestCase
{
    public function test_gross_profit_excludes_twenty_percent_service_fee(): void
    {
        $row = new stdClass();
        $row->note_id = 'note-1';
        $row->work_item_id = 'work-1';
        $row->transaction_date = '2026-09-06';
        $row->customer_name = 'Customer';
        $row->package_sold_amount_rupiah = 215_000;
        $row->parts_total_rupiah = 165_000;
        $row->service_price_rupiah = 10_000;
        $row->package_base_service_price_rupiah = 10_000;
        $row->package_service_extra_rupiah = 0;
        $row->package_profit_rupiah = 40_000;
        $row->refunded_product_component_rupiah = 0;
        $row->refunded_service_component_rupiah = 0;
        $row->sparepart_cogs_rupiah = 120_000;

        $result = (new BreakdownRowMapper())->map($row);

        self::assertSame(50_000, $result['total_service_component_rupiah']);
        self::assertSame(45_000, $result['sparepart_margin_rupiah']);
        self::assertSame(85_000, $result['total_package_gross_profit_rupiah']);
    }
}
