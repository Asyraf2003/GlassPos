<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthPeak500MItemFactory
{
    /** @return array<string, mixed> */
    public function service(): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis peak 500M seed', 'price_rupiah' => 1200000, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /**
     * @param object{id:string,harga_jual:int} $product
     * @return array<string, mixed>
     */
    public function storeStock(object $product): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis sparepart toko peak 500M seed', 'price_rupiah' => 1200000, 'notes' => ''],
            'product_lines' => [['product_id' => $product->id, 'qty' => 2, 'unit_price_rupiah' => 300000]],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /** @return array<string, mixed> */
    public function externalPurchase(): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis pembelian luar peak 500M seed', 'price_rupiah' => 1200000, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [['label' => 'Pembelian luar peak 500M seed', 'qty' => 1, 'unit_cost_rupiah' => 1400000]],
        ];
    }

    /**
     * @param object{id:string,harga_jual:int} $productA
     * @param object{id:string,harga_jual:int} $productB
     * @return array<string, mixed>
     */
    public function packageStoreStock(object $productA, object $productB): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 3400000,
            'service' => ['name' => 'Servis paket peak 500M multi-part seed', 'price_rupiah' => 0, 'notes' => ''],
            'product_lines' => [
                ['product_id' => $productA->id, 'qty' => 1, 'unit_price_rupiah' => 800000],
                ['product_id' => $productB->id, 'qty' => 1, 'unit_price_rupiah' => 600000],
            ],
            'external_purchase_lines' => [$this->blankExternalLine()],
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
