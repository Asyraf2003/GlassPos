<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use RuntimeException;

final class CreateTransactionMonthStress8BPayloadFactory
{
    /** @var list<object{id:string,harga_jual:int,qty_on_hand:int,remaining:int}> */
    private array $products;

    /** @param list<object{id:string,harga_jual:int,qty_on_hand:int}> $products */
    public function __construct(private readonly string $actorId, array $products)
    {
        $this->products = array_map(
            static fn (object $row): object => (object) [
                'id' => $row->id,
                'harga_jual' => $row->harga_jual,
                'qty_on_hand' => $row->qty_on_hand,
                'remaining' => $row->qty_on_hand,
            ],
            $products,
        );
    }

    /** @return list<array<string, mixed>> */
    public function payloads(): array
    {
        $items = new CreateTransactionMonthStress8BItemFactory();
        $payloads = [];

        for ($seq = 1; $seq <= 800; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service stress 8B.', $items->service(), $this->payment($seq, $seq, 560, 144, 1500000, 1100000));
        }

        for ($seq = 801; $seq <= 1800; $seq++) {
            $item = $items->storeStock($this->takeProduct(2));
            $payloads[] = $this->payload($seq, 'Seed nota sparepart toko stress 8B.', $item, $this->payment($seq, $seq - 800, 700, 180, 2200000, 1700000));
        }

        for ($seq = 1801; $seq <= 2700; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota pembelian luar stress 8B.', $items->externalPurchase(), $this->payment($seq, $seq - 1800, 630, 162, 2800000, 2100000));
        }

        for ($seq = 2701; $seq <= 3200; $seq++) {
            $a = $this->takeProduct(1);
            $b = $this->takeProduct(1, $a->id);
            $item = $items->packageStoreStock($a, $b);
            $payloads[] = $this->payload($seq, 'Seed nota paket stress 8B.', $item, $this->payment($seq, $seq - 2700, 350, 90, 3800000, 2900000));
        }

        return $payloads;
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $payment @return array<string, mixed> */
    private function payload(int $seq, string $note, array $item, array $payment): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => sprintf('seed-create-transaction-month-stress-8b-%04d', $seq),
            'note' => ['customer_name' => sprintf('Seed Customer Stress 8B %04d', $seq), 'customer_phone' => '080000000000', 'transaction_date' => $this->date($seq), 'operational_note' => $note],
            'items' => [$item],
            'inline_payment' => $payment,
        ];
    }

    /** @return array<string, mixed> */
    private function payment(int $seq, int $position, int $full, int $partial, int $total, int $partialAmount): array
    {
        if ($position > $full + $partial) {
            return ['decision' => 'skip', 'payment_method' => null, 'paid_at' => $this->date($seq)];
        }

        if ($position > $full) {
            return ['decision' => 'pay_partial', 'payment_method' => $this->method($seq), 'paid_at' => $this->date($seq), 'amount_paid_rupiah' => $partialAmount, 'amount_received_rupiah' => $partialAmount];
        }

        return ['decision' => 'pay_full', 'payment_method' => $this->method($seq), 'paid_at' => $this->date($seq), 'amount_received_rupiah' => $total];
    }

    private function takeProduct(int $qty, ?string $excludeId = null): object
    {
        foreach ($this->products as $product) {
            if ($product->id !== $excludeId && $product->remaining >= $qty) {
                $product->remaining -= $qty;
                return $product;
            }
        }

        throw new RuntimeException('CreateTransactionMonthStress8BPayloadFactory ran out of planned store-stock capacity.');
    }

    private function method(int $seq): string
    {
        return $seq % 2 === 0 ? 'transfer' : 'cash';
    }

    private function date(int $seq): string
    {
        return CreateOnlySeedCalendar::currentMonthDate((($seq - 1) % 28) + 1);
    }
}
