<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

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

final class CustomerTransactionLoadSeeder extends Seeder
{
    public function run(
        AddWorkItemHandler $addWorkItem,
        ReverseNoteStoreStockInventoryOperation $reverseInventory,
    ): void {
        $window = SeedWindow::loadYear();
        $density = SeedDensity::monster();

        $products = DB::table('products')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->select('products.id', 'products.nama_barang', 'products.harga_jual')
            ->whereNull('products.deleted_at')
            ->where('product_inventory.qty_on_hand', '>', 0)
            ->orderBy('products.nama_barang')
            ->get()
            ->values();

        if ($products->count() < 10) {
            $this->command?->warn('CustomerTransactionLoadSeeder dilewati: butuh minimal 10 product aktif.');
            return;
        }

        $noteIds = $this->plannedNoteIds($window['days'], $density);
        $this->purgeSeededTransactions($noteIds, $reverseInventory);

        foreach ($window['days'] as $dayIndex => $day) {
            $notesToday = $this->notesPerDay($day, $density);

            for ($slot = 1; $slot <= $notesToday; $slot++) {
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

                $items = $this->scenarioItems($dayIndex, $slot, $products, (int) $density['max_items_per_note']);
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
                        $errors = json_encode($result->errors(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                        throw new \RuntimeException(sprintf(
                            'Gagal seed transaction load untuk note [%s] line [%d]: %s',
                            $noteId,
                            $lineNo,
                            $errors
                        ));
                    }

                    $lineNo++;
                }
            }
        }

        $this->command?->info('CustomerTransactionLoadSeeder selesai: transaksi monster 1 tahun dibuat.');
    }

    private function notesPerDay(CarbonImmutable $day, array $density): int
    {
        $base = (int) $density['notes_per_day'];
        $spikeDays = [2, 4, 6];

        if (in_array((int) $day->dayOfWeekIso, $spikeDays, true)) {
            return (int) ceil($base * ((int) $density['weekly_spike_multiplier_percent']) / 100);
        }

        return $base;
    }

    /**
     * @param list<CarbonImmutable> $days
     * @return list<string>
     */
    private function plannedNoteIds(array $days, array $density): array
    {
        $ids = [];

        foreach ($days as $day) {
            $notesToday = $this->notesPerDay($day, $density);

            for ($slot = 1; $slot <= $notesToday; $slot++) {
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

        $eventIds = DB::table('note_mutation_events')
            ->whereIn('note_id', $noteIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        if ($eventIds !== []) {
            DB::table('note_mutation_snapshots')
                ->whereIn('note_mutation_event_id', $eventIds)
                ->delete();

            DB::table('note_mutation_events')
                ->whereIn('id', $eventIds)
                ->delete();
        }

        DB::table('refund_component_allocations')
            ->where('customer_refund_id', 'like', 'seed-ref-load-%')
            ->orWhere('customer_payment_id', 'like', 'seed-pay-load-%')
            ->orWhereIn(
                'customer_refund_id',
                DB::table('customer_refunds')
                    ->select('id')
                    ->where('customer_payment_id', 'like', 'seed-pay-load-%')
            )
            ->delete();

        DB::table('customer_refunds')
            ->where('id', 'like', 'seed-ref-load-%')
            ->orWhere('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('payment_component_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('payment_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('customer_payments')
            ->where('id', 'like', 'seed-pay-load-%')
            ->delete();

        $workItemIds = DB::table('work_items')
            ->whereIn('note_id', $noteIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        DB::table('audit_logs')
            ->whereIn('event', [
                'work_item_added',
                'payment_allocated',
                'customer_refund_recorded',
                'paid_work_item_status_corrected',
                'paid_service_only_work_item_corrected',
            ])
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

        DB::table('notes')
            ->whereIn('id', $noteIds)
            ->delete();
    }

    private function noteId(CarbonImmutable $day, int $slot): string
    {
        return sprintf('seed-note-load-%s-%02d', $day->format('Ymd'), $slot);
    }

    private function customerName(int $dayIndex, int $slot): string
    {
        return sprintf('Seed Monster Customer %03d-%02d', $dayIndex + 1, $slot);
    }

    private function customerPhone(int $dayIndex, int $slot): string
    {
        return sprintf('0821%03d%02d88', $dayIndex + 1, $slot);
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
    private function scenarioItems(int $dayIndex, int $slot, Collection $products, int $maxItemsPerNote): array
    {
        $a = $this->pickProduct($products, $dayIndex + $slot + 1);
        $b = $this->pickProduct($products, $dayIndex + ($slot * 2) + 3);
        $c = $this->pickProduct($products, $dayIndex + ($slot * 3) + 5);
        $d = $this->pickProduct($products, $dayIndex + ($slot * 4) + 7);
        $e = $this->pickProduct($products, $dayIndex + ($slot * 5) + 9);
        $f = $this->pickProduct($products, $dayIndex + ($slot * 6) + 11);

        $scenario = ($dayIndex + $slot) % 8;

        $items = match ($scenario) {
            0 => [
                $this->serviceOnlyItem($dayIndex, $slot, 'Servis Ringan', 55000),
            ],
            1 => [
                $this->serviceExternalItem($dayIndex, $slot, 'Servis Luar', 70000, 14000, 2),
            ],
            2 => [
                $this->serviceStockItem($dayIndex, $slot, 'Servis Stok', 68000, $a, 2),
            ],
            3 => [
                $this->serviceOnlyItem($dayIndex, $slot, 'Servis Paket', 60000),
                $this->stockOnlyItem($b, 2),
            ],
            4 => [
                $this->serviceStockItem($dayIndex, $slot, 'Servis Kombinasi', 72000, $c, 1),
                $this->serviceExternalItem($dayIndex, $slot, 'Servis Tambahan', 65000, 12000, 1),
            ],
            5 => [
                $this->serviceOnlyItem($dayIndex, $slot, 'Tune Up', 80000),
                $this->stockOnlyItem($d, 1),
                $this->stockOnlyItem($e, 2),
            ],
            6 => [
                $this->serviceExternalItem($dayIndex, $slot, 'Overhaul Luar', 90000, 18000, 2),
                $this->serviceStockItem($dayIndex, $slot, 'Part Stok', 75000, $f, 1),
            ],
            default => [
                $this->serviceOnlyItem($dayIndex, $slot, 'Servis Awal', 50000),
                $this->serviceStockItem($dayIndex, $slot, 'Servis Tengah', 70000, $a, 1),
                $this->serviceExternalItem($dayIndex, $slot, 'Servis Akhir', 65000, 10000, 1),
                $this->stockOnlyItem($c, 1),
            ],
        };

        return array_slice($items, 0, $maxItemsPerNote);
    }

    /**
     * @param Collection<int, object> $products
     */
    private function pickProduct(Collection $products, int $seed): object
    {
        $index = $seed % $products->count();

        return $products[$index];
    }


    /**
     * @param object $product
     * @return array{
     *   type:string,
     *   sd:array<string, mixed>,
     *   ext:list<array<string, mixed>>,
     *   sto:list<array<string, mixed>>
     * }
     */
    private function stockOnlyItem(object $product, int $qty): array
    {
        return [
            'type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            'sd' => [],
            'ext' => [],
            'sto' => [
                [
                    'product_id' => (string) $product->id,
                    'qty' => $qty,
                    'line_total_rupiah' => ((int) $product->harga_jual) * $qty,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *   type:string,
     *   sd:array<string, mixed>,
     *   ext:list<array<string, mixed>>,
     *   sto:list<array<string, mixed>>
     * }
     */
    private function serviceOnlyItem(int $dayIndex, int $slot, string $label, int $basePrice): array
    {
        return [
            'type' => WorkItem::TYPE_SERVICE_ONLY,
            'sd' => [
                'service_name' => sprintf('%s D%03d S%02d', $label, $dayIndex + 1, $slot),
                'service_price_rupiah' => $basePrice + (($dayIndex % 9) * 1500) + (($slot % 5) * 1000),
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ],
            'ext' => [],
            'sto' => [],
        ];
    }

    /**
     * @param Collection<int, object> $products
     */


    /**
     * @param object $product
     * @return array{
     *   type:string,
     *   sd:array<string, mixed>,
     *   ext:list<array<string, mixed>>,
     *   sto:list<array<string, mixed>>
     * }
     */
    private function serviceStockItem(int $dayIndex, int $slot, string $label, int $basePrice, object $product, int $qty): array
    {
        return [
            'type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'sd' => [
                'service_name' => sprintf('%s D%03d S%02d', $label, $dayIndex + 1, $slot),
                'service_price_rupiah' => $basePrice + (($dayIndex % 7) * 1200) + (($slot % 4) * 900),
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            'ext' => [],
            'sto' => [
                [
                    'product_id' => (string) $product->id,
                    'qty' => $qty,
                    'line_total_rupiah' => ((int) $product->harga_jual) * $qty,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *   type:string,
     *   sd:array<string, mixed>,
     *   ext:list<array<string, mixed>>,
     *   sto:list<array<string, mixed>>
     * }
     */
    private function serviceExternalItem(
        int $dayIndex,
        int $slot,
        string $label,
        int $basePrice,
        int $unitCost,
        int $qty
    ): array {
        return [
            'type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            'sd' => [
                'service_name' => sprintf('%s D%03d S%02d', $label, $dayIndex + 1, $slot),
                'service_price_rupiah' => $basePrice + (($dayIndex % 8) * 1800) + (($slot % 3) * 1200),
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            'ext' => [
                [
                    'cost_description' => sprintf('Komponen luar utama %03d-%02d', $dayIndex + 1, $slot),
                    'unit_cost_rupiah' => $unitCost + (($dayIndex % 6) * 500),
                    'qty' => $qty,
                ],
                [
                    'cost_description' => sprintf('Komponen luar tambahan %03d-%02d', $dayIndex + 1, $slot),
                    'unit_cost_rupiah' => max(1000, $unitCost - 2500),
                    'qty' => 1,
                ],
            ],
            'sto' => [],
        ];
    }
}
