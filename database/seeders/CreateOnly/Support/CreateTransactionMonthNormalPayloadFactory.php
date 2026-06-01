<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthNormalPayloadFactory
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
        $items = new CreateTransactionMonthNormalItemFactory();
        $payloads = [];

        for ($seq = 1; $seq <= 12; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service bulanan normal.', $items->service(900000), 900000);
        }

        for ($seq = 13; $seq <= 20; $seq++) {
            $product = $this->products[($seq - 13) % count($this->products)];
            $unitPrice = max($product->harga_jual, 150000);
            $payloads[] = $this->payload($seq, 'Seed nota sparepart toko bulanan normal.', $items->storeStock($product, $unitPrice), 850000 + $unitPrice);
        }

        for ($seq = 21; $seq <= 26; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota pembelian luar bulanan normal.', $items->externalPurchase(), 1150000);
        }

        for ($seq = 27; $seq <= 28; $seq++) {
            $payloads[] = $this->payload($seq, 'Seed nota service unpaid bulanan normal.', $items->service(600000), null);
        }

        return $payloads;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function payload(int $seq, string $note, array $item, ?int $paidAmount): array
    {
        return [
            '_actor_id' => $this->actorId,
            'idempotency_key' => sprintf('seed-create-transaction-month-normal-%04d', $seq),
            'note' => ['customer_name' => sprintf('Seed Customer Bulanan %03d', $seq), 'customer_phone' => '080000000000', 'transaction_date' => CreateOnlySeedCalendar::currentMonthDate($seq), 'operational_note' => $note],
            'items' => [$item],
            'inline_payment' => $paidAmount === null ? $this->skip($seq) : $this->paid($seq, $paidAmount),
        ];
    }

    /** @return array<string, mixed> */
    private function paid(int $seq, int $paidAmount): array
    {
        return ['decision' => 'pay_full', 'payment_method' => $seq % 2 === 0 ? 'transfer' : 'cash', 'paid_at' => CreateOnlySeedCalendar::currentMonthDate($seq), 'amount_received_rupiah' => $paidAmount];
    }

    /** @return array<string, mixed> */
    private function skip(int $seq): array
    {
        return ['decision' => 'skip', 'payment_method' => null, 'paid_at' => CreateOnlySeedCalendar::currentMonthDate($seq)];
    }
}
