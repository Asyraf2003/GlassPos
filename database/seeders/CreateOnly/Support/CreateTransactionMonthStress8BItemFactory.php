<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateTransactionMonthStress8BItemFactory
{
    /** @return array<string, mixed> */
    public function service(): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis stress 8B seed', 'price_rupiah' => 1500000, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /** @param object{id:string,harga_jual:int} $product */
    public function storeStock(object $product): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis sparepart toko stress 8B seed', 'price_rupiah' => 1400000, 'notes' => ''],
            'product_lines' => [['product_id' => $product->id, 'qty' => 2, 'unit_price_rupiah' => 400000]],
            'external_purchase_lines' => [$this->blankExternalLine()],
        ];
    }

    /** @return array<string, mixed> */
    public function externalPurchase(): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'service' => ['name' => 'Servis pembelian luar stress 8B seed', 'price_rupiah' => 1400000, 'notes' => ''],
            'product_lines' => [$this->blankProductLine()],
            'external_purchase_lines' => [['label' => 'Pembelian luar stress 8B seed', 'qty' => 1, 'unit_cost_rupiah' => 1400000]],
        ];
    }

    /** @param object{id:string,harga_jual:int} $productA @param object{id:string,harga_jual:int} $productB */
    public function packageStoreStock(object $productA, object $productB): array
    {
        return [
            'entry_mode' => 'service',
            'part_source' => 'none',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 3800000,
            'service' => ['name' => 'Servis paket stress 8B multi-part seed', 'price_rupiah' => 0, 'notes' => ''],
            'product_lines' => [
                ['product_id' => $productA->id, 'qty' => 1, 'unit_price_rupiah' => 900000],
                ['product_id' => $productB->id, 'qty' => 1, 'unit_price_rupiah' => 700000],
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
