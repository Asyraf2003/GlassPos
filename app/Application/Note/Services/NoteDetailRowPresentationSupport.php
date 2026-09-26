<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;

final class NoteDetailRowPresentationSupport
{
    public function __construct(
        private readonly NoteDetailRowPrimaryLabelResolver $primaryLabels,
        private readonly NoteDetailRowSubtitleBuilder $subtitles,
    ) {}

    public function typeLabel(WorkItem $item): string
    {
        return match ($item->transactionType()) {
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => 'Produk Toko',
            WorkItem::TYPE_SERVICE_ONLY => 'Servis',
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => 'Servis + Sparepart Toko',
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => 'Servis + Sparepart Luar',
            default => 'Rincian Nota',
        };
    }

    public function lineLabel(WorkItem $item): string
    {
        return $this->primaryLabels->resolve($item);
    }

    public function lineSubtitle(WorkItem $item): ?string
    {
        return $this->subtitles->resolve($item);
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
