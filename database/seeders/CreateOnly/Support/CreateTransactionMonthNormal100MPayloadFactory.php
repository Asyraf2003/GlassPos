<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthNormal100MPayloadFactory
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
        $items = new CreateTransactionMonthNormal100MItemFactory();
        $payloads = [];

        for ($seq = 1; $seq <= 36; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service 100M bulanan normal.', $items->service(900000), 900000);
        }

        for ($seq = 37; $seq <= 63; $seq++) {
            $product = $this->products[($seq - 37) % count($this->products)];
            $payloads[] = $this->payload($seq, 'Seed nota sparepart toko 100M bulanan normal.', $items->storeStock($product), 1600000);
        }

        for ($seq = 64; $seq <= 81; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota pembelian luar 100M bulanan normal.', $items->externalPurchase(), 2600000);
        }

        for ($seq = 82; $seq <= 90; $seq++) {
            $a = $this->products[($seq - 82) % count($this->products)];
            $b = $this->products[($seq - 81) % count($this->products)];
            $payloads[] = $this->payload($seq, 'Seed nota paket 100M bulanan normal.', $items->packageStoreStock($a, $b), 3600000);
        }

        return $payloads;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function payload(int $seq, string $note, array $item, int $total): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => CreateOnlyTransactionSeedIdentity::key('month-normal-100m', $seq),
            'note' => [
                'customer_name' => sprintf('Seed Customer 100M Bulanan %03d', $seq),
                'customer_phone' => '080000000000',
                'transaction_date' => CreateOnlySeedCalendar::currentMonthDate((($seq - 1) % 28) + 1),
                'operational_note' => $note,
            ],
            'items' => [$item],
            'inline_payment' => $this->payment($seq, $total),
        ];
    }

    /** @return array<string, mixed> */
    private function payment(int $seq, int $total): array
    {
        if ($seq % 15 === 0) {
            return ['decision' => 'skip', 'payment_method' => null, 'paid_at' => $this->date($seq)];
        }

        if ($seq % 7 === 0) {
            $amountPaid = $total - 250000;

            return [
                'decision' => 'pay_partial',
                'payment_method' => $this->method($seq),
                'paid_at' => $this->date($seq),
                'amount_paid_rupiah' => $amountPaid,
                'amount_received_rupiah' => $amountPaid,
            ];
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
