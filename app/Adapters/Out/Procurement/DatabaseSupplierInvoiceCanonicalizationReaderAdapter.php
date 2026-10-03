<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierInvoiceCanonicalizationReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierInvoiceCanonicalizationReaderAdapter implements SupplierInvoiceCanonicalizationReaderPort
{
    public function invoiceIdsForProduct(string $productId): array
    {
        return DB::table('supplier_invoices as i')->join('supplier_invoice_lines as l', 'l.supplier_invoice_id', '=', 'i.id')
            ->where('l.product_id', $productId)->where('l.is_current', true)
            ->where('i.lifecycle_status', 'active')->whereNull('i.voided_at')
            ->orderBy('i.id')->distinct()->lockForUpdate()->pluck('i.id')->map(static fn ($id): string => (string) $id)->all();
    }
}
