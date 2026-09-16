<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait BuildsPrimitiveLifecycleFixture
{
    use SeedsMinimalProductFixture;

    private function preparePrimitiveFixture(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return DateTimeImmutable::createFromInterface(Carbon::now());
            }
        });
        foreach ([['p', 47513, 17, 19721], ['q', 28637, 23, 11503], ['r', 33571, 19, 13709]] as [$id, $price, $qty, $cost]) {
            $this->seedMinimalProduct('primitive-'.$id, 'QA-'.strtoupper($id), 'QA '.strtoupper($id), 'QA', null, $price);
            DB::table('product_inventory')->insert(['product_id' => 'primitive-'.$id, 'qty_on_hand' => $qty]);
            DB::table('product_inventory_costing')->insert(['product_id' => 'primitive-'.$id, 'avg_cost_rupiah' => $cost, 'inventory_value_rupiah' => $qty * $cost]);
            DB::table('inventory_movements')->insert([
                'id' => 'opening-'.$id, 'product_id' => 'primitive-'.$id, 'movement_type' => 'stock_in',
                'source_type' => 'seed_fixture', 'source_id' => 'opening-'.$id, 'tanggal_mutasi' => '2026-09-15',
                'qty_delta' => $qty, 'unit_cost_rupiah' => $cost, 'total_cost_rupiah' => $qty * $cost,
            ]);
        }
        DB::table('service_catalog_items')->insert([
            'id' => 'primitive-package-service', 'name' => 'QA package', 'normalized_name' => 'qa package',
            'default_price_rupiah' => 41983, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('service_product_templates')->insert([
            'id' => 'primitive-template', 'product_id' => 'primitive-q', 'service_catalog_item_id' => 'primitive-package-service',
            'default_service_price_rupiah' => 41983, 'default_package_total_rupiah' => 99257,
            'is_active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('service_product_template_lines')->insert([
            'id' => 'primitive-template-q', 'service_product_template_id' => 'primitive-template', 'product_id' => 'primitive-q',
            'qty' => 2, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function primitiveItems(int $servicePrice = 63719): array
    {
        return [
            ['entry_mode' => 'product', 'product_lines' => [['product_id' => 'primitive-p', 'qty' => 3, 'unit_price_rupiah' => 47513]]],
            ['entry_mode' => 'service', 'part_source' => 'none', 'service' => ['name' => 'QA service', 'price_rupiah' => $servicePrice]],
            ['entry_mode' => 'service', 'part_source' => 'store_stock', 'pricing_mode' => 'package_auto_split',
                'requires_service_product_template' => true, 'package_total_rupiah' => 99257,
                'service' => ['name' => 'QA package', 'price_rupiah' => 41983],
                'product_lines' => [['product_id' => 'primitive-q', 'qty' => 2, 'unit_price_rupiah' => 28637]]],
            ['entry_mode' => 'service', 'part_source' => 'external_purchase',
                'service' => ['name' => 'QA outside', 'price_rupiah' => 37291],
                'external_purchase_lines' => [['label' => 'QA outside part', 'qty' => 1, 'unit_cost_rupiah' => 53127]]],
        ];
    }

    private function primitiveWorkspace(array $items, string $key): array
    {
        return ['idempotency_key' => $key, 'reason' => $key,
            'note' => ['customer_name' => 'QA primitive', 'transaction_date' => '2026-09-15'],
            'items' => $items, 'inline_payment' => ['decision' => 'skip']];
    }

    private function primitivePayment(string $key, int $amount, string $method, ?int $tender = null): array
    {
        return ['selected_row_ids' => DB::table('work_items')->where('status', 'open')->pluck('id')->all(), 'idempotency_key' => $key, 'payment_scope' => 'partial', 'payment_method' => $method,
            'paid_at' => '2026-09-15', 'amount_paid' => $amount, 'amount_received' => $tender];
    }

    private function advancePrimitiveTime(): void
    {
        Carbon::setTestNow(Carbon::now()->addMinute());
    }

    private function primitiveRows(string $table): string
    {
        return DB::table($table)->orderBy($table === 'customer_payment_cash_details' ? 'customer_payment_id' : 'id')->get()->toJson();
    }
}
