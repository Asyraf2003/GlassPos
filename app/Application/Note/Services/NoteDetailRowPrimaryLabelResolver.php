<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;

final class NoteDetailRowPrimaryLabelResolver
{
    public function __construct(
        private readonly NoteDetailProductLabelResolver $products,
    ) {
    }

    public function resolve(WorkItem $item): string
    {
        $serviceName = trim((string) ($item->serviceDetail()?->serviceName() ?? ''));

        return match ($item->transactionType()) {
            WorkItem::TYPE_SERVICE_ONLY => $serviceName !== '' ? $serviceName : 'Servis',
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => $this->storeStockPrimaryLabel($item->storeStockLines()) ?? 'Produk',
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => $serviceName !== ''
                ? $serviceName
                : ($this->storeStockPrimaryLabel($item->storeStockLines()) ?? 'Servis + Sparepart Toko'),
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => $serviceName !== ''
                ? $serviceName
                : ($this->externalPrimaryLabel($item->externalPurchaseLines()) ?? 'Servis + Sparepart Luar'),
            default => $serviceName !== '' ? $serviceName : 'Rincian Nota',
        };
    }

    /**
     * @param array<int, StoreStockLine> $lines
     */
    private function storeStockPrimaryLabel(array $lines): ?string
    {
        if ($lines === []) {
            return null;
        }

        $label = $this->products->resolve($lines[0]->productId());
        $remaining = count($lines) - 1;

        return $remaining > 0 ? $label . ' +' . $remaining . ' item' : $label;
    }

    /**
     * @param array<int, ExternalPurchaseLine> $lines
     */
    private function externalPrimaryLabel(array $lines): ?string
    {
        if ($lines === []) {
            return null;
        }

        $first = trim($lines[0]->costDescription());
        if ($first === '') {
            return null;
        }

        $remaining = count($lines) - 1;

        return $remaining > 0 ? $first . ' +' . $remaining . ' item' : $first;
    }
}
