<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;

final class NoteBillingProjectionSupport
{
    public function componentKey(string $type, string $refId): string
    {
        return trim($type) . '::' . trim($refId);
    }

    public function isProductComponent(string $type): bool
    {
        return in_array($type, [
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART,
        ], true);
    }

    public function componentLabel(string $type): string
    {
        return match ($type) {
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM => 'Produk Toko',
            PaymentComponentType::SERVICE_STORE_STOCK_PART => 'Sparepart Toko',
            PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART => 'Sparepart Luar',
            PaymentComponentType::SERVICE_FEE => 'Jasa',
            default => 'Komponen Tagihan',
        };
    }

    public function componentGroupLabel(string $type): string
    {
        return $this->isProductComponent($type) ? 'Produk' : 'Jasa';
    }

    public function domainTypeLabel(WorkItem $item): string
    {
        return match ($item->transactionType()) {
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => 'Produk Toko',
            WorkItem::TYPE_SERVICE_ONLY => 'Servis',
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => 'Servis + Sparepart Toko',
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => 'Servis + Sparepart Luar',
            default => 'Rincian Nota',
        };
    }
}
