<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthPeak500MPayloadFactory
{
    /** @var list<object{id:string,harga_jual:int}> */
    private array $products;

    /** @param list<object{id:string,harga_jual:int}> $products */
    public function __construct(private readonly string $actorId, array $products)
    {
        $this->products = $products;
    }

    /** @return list<array<string, mixed>> */
    public function payloads(): array
    {
        $items = new CreateTransactionMonthPeak500MItemFactory();
        $payloads = [];

        for ($seq = 1; $seq <= 80; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service peak 500M.', $items->service(), $this->payment($seq, $seq, 66, 10, 1200000, 900000));
        }

        for ($seq = 81; $seq <= 170; $seq++) {
            $product = $this->products[($seq - 81) % count($this->products)];
            $payloads[] = $this->payload($seq, 'Seed nota sparepart toko peak 500M.', $items->storeStock($product), $this->payment($seq, $seq - 80, 74, 12, 1800000, 1400000));
        }

        for ($seq = 171; $seq <= 240; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota pembelian luar peak 500M.', $items->externalPurchase(), $this->payment($seq, $seq - 170, 54, 10, 2600000, 2000000));
        }

        for ($seq = 241; $seq <= 280; $seq++) {
            $a = $this->products[($seq - 241) % count($this->products)];
            $b = $this->products[($seq - 240) % count($this->products)];
            $payloads[] = $this->payload($seq, 'Seed nota paket peak 500M.', $items->packageStoreStock($a, $b), $this->payment($seq, $seq - 240, 32, 6, 3400000, 2700000));
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
            'idempotency_key' => sprintf('seed-create-transaction-month-peak-500m-%04d', $seq),
            'note' => [
                'customer_name' => sprintf('Seed Customer Peak 500M %03d', $seq),
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
            return ['decision' => 'pay_partial', 'payment_method' => $this->method($seq), 'paid_at' => $this->date($seq), 'amount_paid_rupiah' => $partialAmount, 'amount_received_rupiah' => $partialAmount];
        }

        return ['decision' => 'pay_full', 'payment_method' => $this->method($seq), 'paid_at' => $this->date($seq), 'amount_received_rupiah' => $total];
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
