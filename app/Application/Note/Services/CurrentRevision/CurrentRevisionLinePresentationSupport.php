<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

final class CurrentRevisionLinePresentationSupport
{
    public function typeLabel(string $transactionType): string
    {
        return match ($transactionType) {
            'store_stock_sale_only' => 'Produk Toko',
            'service_only' => 'Servis',
            'service_with_store_stock_part' => 'Servis + Sparepart Toko',
            'service_with_external_purchase' => 'Servis + Sparepart Luar',
            default => 'Rincian Nota',
        };
    }

    public function refundPreviewLabel(int $storeStockCount, int $externalPurchaseCount): string
    {
        if ($storeStockCount > 0 && $externalPurchaseCount > 0) {
            return 'Uang balik mungkin, stok toko kembali, komponen luar dinetralkan.';
        }

        if ($storeStockCount > 0) {
            return 'Pengembalian uang; pilih apakah barang kembali ke stok toko.';
        }

        if ($externalPurchaseCount > 0) {
            return 'Uang balik mungkin, komponen luar tidak memicu stok toko.';
        }

        return 'Pengembalian dana sederhana mengikuti uang yang memang sudah masuk.';
    }
}
