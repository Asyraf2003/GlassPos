<?php

declare(strict_types=1);

namespace Database\Seeders\Transaction;

use App\Application\Inventory\Services\ReverseNoteStoreStockInventoryOperation;
use App\Application\Note\UseCases\AddWorkItemHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedDensity;
use Database\Seeders\Support\SeedWindow;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerTransactionBaselineSeeder extends Seeder
{
    public function run(
        AddWorkItemHandler $addWorkItem,
        ReverseNoteStoreStockInventoryOperation $reverseInventory,
    ): void {
        $window = SeedWindow::baselineMonth();
        $density = SeedDensity::baseline();

        $products = DB::table('products')
            ->select('id', 'nama_barang', 'harga_jual')
            ->whereNull('deleted_at')
            ->orderBy('nama_barang')
            ->limit(6)
            ->get()
            ->values();

        if ($products->count() < 3) {
            $this->command?->warn('CustomerTransactionBaselineSeeder dilewati: butuh minimal 3 product aktif.');
            return;
        }

        $this->seedInventoryFoundation($products);

        $noteIds = $this->plannedNoteIds($window['days'], $density['notes_per_day']);
        $this->purgeSeededTransactions($noteIds, $reverseInventory);

        foreach ($window['days'] as $dayIndex => $day) {
            for ($slot = 1; $slot <= $density['notes_per_day']; $slot++) {
                $noteId = $this->noteId($day, $slot);

                DB::table('notes')->updateOrInsert(
                    ['id' => $noteId],
                    [
                        'customer_name' => $this->customerName($dayIndex, $slot),
                        'customer_phone' => $this->customerPhone($dayIndex, $slot),
                        'transaction_date' => $day->format('Y-m-d'),
                        'total_rupiah' => 0,
                    ]
                );

                $items = $this->scenarioItems($dayIndex, $slot, $products);
                $lineNo = 1;

                foreach ($items as $item) {
                    $result = $addWorkItem->handle(
                        $noteId,
                        $lineNo,
                        $item['type'],
                        $item['sd'],
                        $item['ext'],
                        $item['sto'],
                    );

                    if ($result->isFailure()) {
                        $errors = json_encode($result->errors(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                        throw new \RuntimeException(
                            sprintf(
                                'Gagal seed transaction baseline untuk note [%s] line [%d]: %s',
                                $noteId,
                                $lineNo,
                                $errors
                            )
                        );
                    }

                    $lineNo++;
                }
            }
        }

        $this->command?->info('CustomerTransactionBaselineSeeder selesai: baseline transaksi 1 bulan dibuat.');
    }

    /**
     * @param Collection<int, object> $products
     */
    private function seedInventoryFoundation(Collection $products): void
    {
        foreach ($products as $index => $product) {
            $qtyOnHand = 400 + (($index + 1) * 50);
            $avgCost = max(1000, (int) floor(((int) $product->harga_jual) * 0.6));
            $inventoryValue = $qtyOnHand * $avgCost;

            DB::table('product_inventory')->updateOrInsert(
                ['product_id' => (string) $product->id],
                ['qty_on_hand' => $qtyOnHand]
            );

            DB::table('product_inventory_costing')->updateOrInsert(
                ['product_id' => (string) $product->id],
                [
                    'avg_cost_rupiah' => $avgCost,
                    'inventory_value_rupiah' => $inventoryValue,
                ]
            );
        }
    }

    /**
     * @param list<CarbonImmutable> $days
     * @return list<string>
     */
    private function plannedNoteIds(array $days, int $notesPerDay): array
    {
        $ids = [];

        foreach ($days as $day) {
            for ($slot = 1; $slot <= $notesPerDay; $slot++) {
                $ids[] = $this->noteId($day, $slot);
            }
        }

        return $ids;
    }

    /**
     * @param list<string> $noteIds
     */
    private function purgeSeededTransactions(
        array $noteIds,
        ReverseNoteStoreStockInventoryOperation $reverseInventory,
    ): void {
        foreach ($noteIds as $noteId) {
            $reverseInventory->execute($noteId, new DateTimeImmutable('today'));
        }

        DB::table('refund_component_allocations')
            ->where('customer_refund_id', 'like', 'seed-ref-bl-%')
            ->delete();

        DB::table('payment_component_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-bl-%')
            ->delete();

        DB::table('payment_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-bl-%')
            ->delete();

        DB::table('customer_refunds')
            ->where('id', 'like', 'seed-ref-bl-%')
            ->delete();

        DB::table('customer_payments')
            ->where('id', 'like', 'seed-pay-bl-%')
            ->delete();

        $workItemIds = DB::table('work_items')
            ->whereIn('note_id', $noteIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        DB::table('refund_component_allocations')
            ->whereIn('note_id', $noteIds)
            ->delete();

        DB::table('payment_component_allocations')
            ->whereIn('note_id', $noteIds)
            ->delete();

        DB::table('payment_allocations')
            ->whereIn('note_id', $noteIds)
            ->delete();

        DB::table('customer_refunds')
            ->whereIn('note_id', $noteIds)
            ->delete();

        if ($workItemIds !== []) {
            DB::table('refund_component_allocations')
                ->whereIn('work_item_id', $workItemIds)
                ->delete();

            DB::table('payment_component_allocations')
                ->whereIn('work_item_id', $workItemIds)
                ->delete();

            DB::table('work_item_service_details')
                ->whereIn('work_item_id', $workItemIds)
                ->delete();

            DB::table('work_item_external_purchase_lines')
                ->whereIn('work_item_id', $workItemIds)
                ->delete();

            DB::table('work_item_store_stock_lines')
                ->whereIn('work_item_id', $workItemIds)
                ->delete();
        }

        DB::table('work_items')
            ->whereIn('note_id', $noteIds)
            ->delete();

        $mutationEventIds = DB::table('note_mutation_events')
            ->whereIn('note_id', $noteIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        if ($mutationEventIds !== []) {
            DB::table('note_mutation_snapshots')
                ->whereIn('note_mutation_event_id', $mutationEventIds)
                ->delete();

            DB::table('note_mutation_events')
                ->whereIn('id', $mutationEventIds)
                ->delete();
        }

        DB::table('notes')
            ->whereIn('id', $noteIds)
            ->delete();

        foreach ($noteIds as $noteId) {
            DB::table('audit_logs')
                ->where('event', 'work_item_added')
                ->where('context', 'like', '%"note_id":"'.$noteId.'"%')
                ->delete();
        }
    }

    private function noteId(CarbonImmutable $day, int $slot): string
    {
        return sprintf('seed-note-bl-%s-%02d', $day->format('Ymd'), $slot);
    }

    private function customerName(int $dayIndex, int $slot): string
    {
        return sprintf('Seed Baseline Customer %02d-%02d', $dayIndex + 1, $slot);
    }

    private function customerPhone(int $dayIndex, int $slot): string
    {
        return sprintf('0819%02d%02d1234', $dayIndex + 1, $slot);
    }

    /**
     * @param Collection<int, object> $products
     * @return list<array{
     *   type:string,
     *   sd:array<string, mixed>,
     *   ext:list<array<string, mixed>>,
     *   sto:list<array<string, mixed>>
     * }>
     */
    private function scenarioItems(int $dayIndex, int $slot, Collection $products): array
    {
        $a = $products[0];
        $b = $products[1];
        $c = $products[2];

        $scenario = ($slot - 1) % 5;

        return match ($scenario) {
            0 => [
                [
                    'type' => WorkItem::TYPE_SERVICE_ONLY,
                    'sd' => [
                        'service_name' => sprintf('Servis Ringan D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 50000 + ($dayIndex * 2500),
                        'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                    ],
                    'ext' => [],
                    'sto' => [],
                ],
            ],
            1 => [
                [
                    'type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
                    'sd' => [
                        'service_name' => sprintf('Servis Luar D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 70000 + ($dayIndex * 3000),
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                    'ext' => [
                        [
                            'cost_description' => 'Komponen luar utama',
                            'unit_cost_rupiah' => 15000 + ($dayIndex * 500),
                            'qty' => 2,
                        ],
                        [
                            'cost_description' => 'Komponen luar tambahan',
                            'unit_cost_rupiah' => 10000 + ($slot * 250),
                            'qty' => 1,
                        ],
                    ],
                    'sto' => [],
                ],
            ],
            2 => [
                [
                    'type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                    'sd' => [
                        'service_name' => sprintf('Servis Stok D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 65000 + ($slot * 1500),
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                    'ext' => [],
                    'sto' => [
                        [
                            'product_id' => (string) $a->id,
                            'qty' => 1 + ($slot % 2),
                            'line_total_rupiah' => ((int) $a->harga_jual) * (1 + ($slot % 2)),
                        ],
                    ],
                ],
            ],
            3 => [
                [
                    'type' => WorkItem::TYPE_SERVICE_ONLY,
                    'sd' => [
                        'service_name' => sprintf('Jasa Multi D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 45000 + ($slot * 1000),
                        'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                    ],
                    'ext' => [],
                    'sto' => [],
                ],
                [
                    'type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                    'sd' => [],
                    'ext' => [],
                    'sto' => [
                        [
                            'product_id' => (string) $b->id,
                            'qty' => 2,
                            'line_total_rupiah' => ((int) $b->harga_jual) * 2,
                        ],
                    ],
                ],
            ],
            default => [
                [
                    'type' => WorkItem::TYPE_SERVICE_ONLY,
                    'sd' => [
                        'service_name' => sprintf('Servis Paket D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 55000 + ($dayIndex * 2000),
                        'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                    ],
                    'ext' => [],
                    'sto' => [],
                ],
                [
                    'type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
                    'sd' => [
                        'service_name' => sprintf('Servis Paket Luar D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 60000 + ($slot * 1200),
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                    'ext' => [
                        [
                            'cost_description' => 'Seal luar',
                            'unit_cost_rupiah' => 12000,
                            'qty' => 1,
                        ],
                    ],
                    'sto' => [],
                ],
                [
                    'type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                    'sd' => [
                        'service_name' => sprintf('Servis Paket Stok D%02d S%02d', $dayIndex + 1, $slot),
                        'service_price_rupiah' => 70000 + ($dayIndex * 1500),
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                    'ext' => [],
                    'sto' => [
                        [
                            'product_id' => (string) $c->id,
                            'qty' => 1,
                            'line_total_rupiah' => (int) $c->harga_jual,
                        ],
                    ],
                ],
            ],
        };
    }
}
