<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Core\IdentityAccess\Role\Role;
use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlySeedCalendar;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateTransactionWeekSeeder extends CreateOnlySeeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        /** @var CreateTransactionWorkspaceHandler $handler */
        $handler = app(CreateTransactionWorkspaceHandler::class);

        $actorId = $this->resolveActorId();
        $products = $this->storeStockProducts();

        $created = 0;
        $replayed = 0;

        foreach ($this->payloads($actorId, $products) as $payload) {
            $before = (int) DB::table('notes')->count();

            $result = $handler->handle($payload);

            if ($result->isFailure()) {
                throw new RuntimeException(
                    'Create transaction week seed failed: '.($result->message() ?? 'unknown failure')
                );
            }

            $after = (int) DB::table('notes')->count();

            if ($after > $before) {
                $created++;
            } else {
                $replayed++;
            }
        }

        $this->command?->info(sprintf(
            'create-only transaction week notes: planned=%d created=%d replayed=%d',
            count($this->payloads($actorId, $products)),
            $created,
            $replayed
        ));
    }

    private function resolveActorId(): string
    {
        $actorId = DB::table('actor_accesses')
            ->whereIn('role', [Role::KASIR, Role::ADMIN])
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [Role::KASIR])
            ->orderBy('actor_id')
            ->value('actor_id');

        $actorId = is_string($actorId) ? trim($actorId) : '';

        if ($actorId === '') {
            throw new RuntimeException('CreateTransactionWeekSeeder requires cashier/admin actor access.');
        }

        return $actorId;
    }

    /**
     * @return list<object{id:string,harga_jual:int}>
     */
    private function storeStockProducts(): array
    {
        $rows = DB::table('products')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'products.id')
            ->join('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->where('product_inventory.qty_on_hand', '>=', 20)
            ->where('products.harga_jual', '>', 0)
            ->orderBy('products.id')
            ->limit(6)
            ->get([
                'products.id',
                'products.harga_jual',
            ])
            ->map(static fn (object $row): object => (object) [
                'id' => (string) $row->id,
                'harga_jual' => (int) $row->harga_jual,
            ])
            ->values()
            ->all();

        if (count($rows) < 4) {
            throw new RuntimeException('CreateTransactionWeekSeeder requires at least 4 stocked products with qty_on_hand >= 20.');
        }

        return $rows;
    }

    /**
     * @param list<object{id:string,harga_jual:int}> $products
     * @return list<array<string, mixed>>
     */
    private function payloads(string $actorId, array $products): array
    {
        return [
            $this->serviceOnlyFullCash($actorId, 1),
            $this->serviceExternalPartialTransfer($actorId, 2),
            $this->serviceStoreStockFullCash($actorId, 3, $products[0]),
            $this->packageStoreStockMultiFullCash($actorId, 4, $products[1], $products[2]),
            $this->serviceOnlySkipPayment($actorId, 5),
            $this->serviceExternalFullCash($actorId, 6),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceOnlyFullCash(string $actorId, int $day): array
    {
        $total = 150000;

        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0001',
            'note' => $this->note('Seed Customer Mingguan 001', $day, 'Seed nota service only full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis ringan seed',
                    'price_rupiah' => $total,
                    'notes' => '',
                ],
                'product_lines' => [$this->blankProductLine()],
                'external_purchase_lines' => [$this->blankExternalLine()],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
                'amount_received_rupiah' => 200000,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceExternalPartialTransfer(string $actorId, int $day): array
    {
        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0002',
            'note' => $this->note('Seed Customer Mingguan 002', $day, 'Seed nota service external partial transfer.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis bearing external seed',
                    'price_rupiah' => 80000,
                    'notes' => '',
                ],
                'product_lines' => [$this->blankProductLine()],
                'external_purchase_lines' => [[
                    'label' => 'Bearing external seed',
                    'qty' => 1,
                    'unit_cost_rupiah' => 120000,
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'pay_partial',
                'payment_method' => 'transfer',
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
                'amount_paid_rupiah' => 100000,
            ],
        ];
    }

    /**
     * @param object{id:string,harga_jual:int} $product
     * @return array<string, mixed>
     */
    private function serviceStoreStockFullCash(string $actorId, int $day, object $product): array
    {
        $unitPrice = max($product->harga_jual, 25000);
        $servicePrice = 125000;
        $total = $servicePrice + ($unitPrice * 2);

        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0003',
            'note' => $this->note('Seed Customer Mingguan 003', $day, 'Seed nota service store stock full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis sparepart toko seed',
                    'price_rupiah' => $servicePrice,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => $product->id,
                    'qty' => 2,
                    'unit_price_rupiah' => $unitPrice,
                ]],
                'external_purchase_lines' => [$this->blankExternalLine()],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
                'amount_received_rupiah' => $total,
            ],
        ];
    }

    /**
     * @param object{id:string,harga_jual:int} $productA
     * @param object{id:string,harga_jual:int} $productB
     * @return array<string, mixed>
     */
    private function packageStoreStockMultiFullCash(string $actorId, int $day, object $productA, object $productB): array
    {
        $unitA = max($productA->harga_jual, 50000);
        $unitB = max($productB->harga_jual, 30000);
        $partsTotal = ($unitA * 2) + $unitB;
        $packageTotal = $partsTotal + 120000;

        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0004',
            'note' => $this->note('Seed Customer Mingguan 004', $day, 'Seed nota package auto split multi-product.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => $packageTotal,
                'service' => [
                    'name' => 'Servis paket multi-part seed',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => $productA->id,
                        'qty' => 2,
                        'unit_price_rupiah' => $unitA,
                    ],
                    [
                        'product_id' => $productB->id,
                        'qty' => 1,
                        'unit_price_rupiah' => $unitB,
                    ],
                ],
                'external_purchase_lines' => [$this->blankExternalLine()],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
                'amount_received_rupiah' => $packageTotal,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceOnlySkipPayment(string $actorId, int $day): array
    {
        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0005',
            'note' => $this->note('Seed Customer Mingguan 005', $day, 'Seed nota service only unpaid.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis unpaid seed',
                    'price_rupiah' => 175000,
                    'notes' => '',
                ],
                'product_lines' => [$this->blankProductLine()],
                'external_purchase_lines' => [$this->blankExternalLine()],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceExternalFullCash(string $actorId, int $day): array
    {
        $total = 275000;

        return [
            '_actor_id' => $actorId,
            'idempotency_key' => 'seed-create-transaction-week-0006-v2',
            'note' => $this->note('Seed Customer Mingguan 006', $day, 'Seed nota service external full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => [
                    'name' => 'Servis external seed',
                    'price_rupiah' => 175000,
                    'notes' => '',
                ],
                'product_lines' => [$this->blankProductLine()],
                'external_purchase_lines' => [[
                    'label' => 'Pembelian luar seed',
                    'qty' => 1,
                    'unit_cost_rupiah' => 100000,
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => CreateOnlySeedCalendar::currentMonthDate($day),
                'amount_received_rupiah' => $total,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function note(string $customerName, int $day, string $operationalNote): array
    {
        return [
            'customer_name' => $customerName,
            'customer_phone' => '080000000000',
            'transaction_date' => CreateOnlySeedCalendar::currentMonthDate($day),
            'operational_note' => $operationalNote,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankProductLine(): array
    {
        return [
            'product_id' => '',
            'qty' => '',
            'unit_price_rupiah' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankExternalLine(): array
    {
        return [
            'label' => '',
            'qty' => '',
            'unit_cost_rupiah' => '',
        ];
    }
}
