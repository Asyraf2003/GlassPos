<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use RuntimeException;

final class CreateTransactionMonthStress10BPayloadFactory
{
    private array $products;

    public function __construct(private readonly string $actorId, array $products)
    {
        $this->products = array_map(
            static fn (object $row): object => (object) [
                'id' => (string) $row->id,
                'harga_jual' => (int) $row->harga_jual,
                'qty_on_hand' => (int) $row->qty_on_hand,
                'remaining' => (int) $row->qty_on_hand,
            ],
            $products,
        );
    }

    /** @return list<array<string, mixed>> */
    public function payloads(): array
    {
        $items = new CreateTransactionMonthStress10BItemFactory();
        $payloads = [];

        for ($seq = 1; $seq <= 1000; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service stress 10B.', $items->service(), $this->payment($seq, $seq, 700, 180, 1500000, 1100000));
        }

        for ($seq = 1001; $seq <= 2250; $seq++) {
            $item = $items->storeStock($this->takeProduct(2));
            $payloads[] = $this->payload($seq, 'Seed nota sparepart toko stress 10B.', $item, $this->payment($seq, $seq - 1000, 875, 225, 2200000, 1700000));
        }

        for ($seq = 2251; $seq <= 3375; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota pembelian luar stress 10B.', $items->externalPurchase(), $this->payment($seq, $seq - 2250, 788, 202, 2800000, 2100000));
        }

        for ($seq = 3376; $seq <= 4000; $seq++) {
            $a = $this->takeProduct(1);
            $b = $this->takeProduct(1, $a->id);
            $item = $items->packageStoreStock($a, $b);
            $payloads[] = $this->payload($seq, 'Seed nota paket stress 10B.', $item, $this->payment($seq, $seq - 3375, 438, 112, 4160000, 3200000));
        }

        return $payloads;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    private function payload(int $seq, string $note, array $item, array $payment): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => CreateOnlyTransactionSeedIdentity::key('month-stress-10b', $seq),
            'note' => [
                'customer_name' => sprintf('Seed Customer Stress 10B %04d', $seq),
                'customer_phone' => '080000000000',
                'transaction_date' => $this->date($seq),
                'operational_note' => $note,
            ],
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
            return [
                'decision' => 'pay_partial',
                'payment_method' => $this->method($seq),
                'paid_at' => $this->date($seq),
                'amount_paid_rupiah' => $partialAmount,
                'amount_received_rupiah' => $partialAmount,
            ];
        }

        return [
            'decision' => 'pay_full',
            'payment_method' => $this->method($seq),
            'paid_at' => $this->date($seq),
            'amount_received_rupiah' => $total,
        ];
    }

    private function takeProduct(int $qty, ?string $excludeId = null): object
    {
        foreach ($this->products as $product) {
            if ($product->id !== $excludeId && $product->remaining >= $qty) {
                $product->remaining -= $qty;

                return $product;
            }
        }

        throw new RuntimeException('CreateTransactionMonthStress10BPayloadFactory ran out of planned store-stock capacity.');
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
