<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthNormalItemFactory
{
    /** @return array<string, mixed> */
    public function service(int $price): array
    {
        return [
            'entry_mode' => 'service', 'part_source' => 'none',
            'service' => ['name' => 'Servis normal bulanan seed', 'price_rupiah' => $price, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /**
     * @param object{id:string,harga_jual:int} $product
     * @return array<string, mixed>
     */
    public function storeStock(object $product, int $unitPrice): array
    {
        return [
            'entry_mode' => 'service', 'part_source' => 'none',
            'service' => ['name' => 'Servis sparepart toko bulanan seed', 'price_rupiah' => 850000, 'notes' => ''],
            'product_lines' => [['product_id' => $product->id, 'qty' => 1, 'unit_price_rupiah' => $unitPrice]],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /** @return array<string, mixed> */
    public function externalPurchase(): array
    {
        return [
            'entry_mode' => 'service', 'part_source' => 'none',
            'service' => ['name' => 'Servis pembelian luar bulanan seed', 'price_rupiah' => 900000, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [['label' => 'Pembelian luar bulanan seed', 'qty' => 1, 'unit_cost_rupiah' => 250000]],
        ];
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
