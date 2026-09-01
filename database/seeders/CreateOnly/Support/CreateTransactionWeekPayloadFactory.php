<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionWeekPayloadFactory
{
    /** @var list<object{id:string,harga_jual:int,qty_on_hand:int}> */
    private array $products;

    /** @param list<object{id:string,harga_jual:int,qty_on_hand:int}> $products */
    public function __construct(private readonly string $actorId, array $products)
    {
        $this->products = $products;
    }

    /** @return list<array<string, mixed>> */
    public function payloads(): array
    {
        return [
            $this->serviceOnlyFullCash(1),
            $this->serviceExternalPartialTransfer(2),
            $this->serviceStoreStockFullCash(3, $this->products[0]),
            $this->packageStoreStockMultiFullCash(4, $this->products[1], $this->products[2]),
            $this->serviceOnlySkipPayment(5),
            $this->serviceExternalFullCash(6),
        ];
    }

    /** @return array<string, mixed> */
    private function serviceOnlyFullCash(int $day): array
    {
        $total = 150000;

        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(1),
            'note' => $this->note('Seed Customer Mingguan 001', $day, 'Seed nota service only full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis ringan seed', 'price_rupiah' => $total, 'notes' => ''],
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

    /** @return array<string, mixed> */
    private function serviceExternalPartialTransfer(int $day): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(2),
            'note' => $this->note('Seed Customer Mingguan 002', $day, 'Seed nota service external partial transfer.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis bearing external seed', 'price_rupiah' => 80000, 'notes' => ''],
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
     * @param object{id:string,harga_jual:int,qty_on_hand:int} $product
     * @return array<string, mixed>
     */
    private function serviceStoreStockFullCash(int $day, object $product): array
    {
        $unitPrice = max($product->harga_jual, 25000);
        $servicePrice = 125000;
        $total = $servicePrice + ($unitPrice * 2);

        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(3),
            'note' => $this->note('Seed Customer Mingguan 003', $day, 'Seed nota service store stock full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis sparepart toko seed', 'price_rupiah' => $servicePrice, 'notes' => ''],
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
     * @param object{id:string,harga_jual:int,qty_on_hand:int} $productA
     * @param object{id:string,harga_jual:int,qty_on_hand:int} $productB
     * @return array<string, mixed>
     */
    private function packageStoreStockMultiFullCash(int $day, object $productA, object $productB): array
    {
        $unitA = max($productA->harga_jual, 50000);
        $unitB = max($productB->harga_jual, 30000);
        $partsTotal = ($unitA * 2) + $unitB;
        $packageTotal = $partsTotal + 120000;

        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(4),
            'note' => $this->note('Seed Customer Mingguan 004', $day, 'Seed nota package auto split multi-product.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => $packageTotal,
                'service' => ['name' => 'Servis paket multi-part seed', 'price_rupiah' => 0, 'notes' => ''],
                'product_lines' => [
                    ['product_id' => $productA->id, 'qty' => 2, 'unit_price_rupiah' => $unitA],
                    ['product_id' => $productB->id, 'qty' => 1, 'unit_price_rupiah' => $unitB],
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

    /** @return array<string, mixed> */
    private function serviceOnlySkipPayment(int $day): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(5),
            'note' => $this->note('Seed Customer Mingguan 005', $day, 'Seed nota service only unpaid.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis unpaid seed', 'price_rupiah' => 175000, 'notes' => ''],
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

    /** @return array<string, mixed> */
    private function serviceExternalFullCash(int $day): array
    {
        $total = 275000;

        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => $this->key(6),
            'note' => $this->note('Seed Customer Mingguan 006', $day, 'Seed nota service external full cash.'),
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis external seed', 'price_rupiah' => 175000, 'notes' => ''],
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

    /** @return array<string, mixed> */
    private function note(string $customerName, int $day, string $operationalNote): array
    {
        return [
            'customer_name' => $customerName,
            'customer_phone' => '080000000000',
            'transaction_date' => CreateOnlySeedCalendar::currentMonthDate($day),
            'operational_note' => $operationalNote,
        ];
    }

    private function key(int $sequence): string
    {
        return CreateOnlyTransactionSeedIdentity::key('week', $sequence);
    }

    /** @return array<string, mixed> */
    private function blankProductLine(): array
    {
        return ['product_id' => '', 'qty' => '', 'unit_price_rupiah' => ''];
    }

    /** @return array<string, mixed> */
    private function blankExternalLine(): array
    {
        return ['label' => '', 'qty' => '', 'unit_cost_rupiah' => ''];
    }
}
