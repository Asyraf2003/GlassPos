<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Support\Facades\DB;
use LogicException;

trait LoadsCurrentSupplierInvoiceWriteSnapshot
{
    /** @return array{last_revision_no:int, snapshot:array<string, mixed>} */
    private function loadCurrentInvoiceWriteSnapshot(string $supplierInvoiceId): array
    {
        $invoice = $this->reader->getByIdForUpdate($supplierInvoiceId);
        if ($invoice === null) {
            throw new LogicException('Supplier invoice tidak ditemukan untuk proses update.');
        }

        return [
            'last_revision_no' => (int) DB::table('supplier_invoices')->where('id', $supplierInvoiceId)->value('last_revision_no'),
            'snapshot' => $this->toVersionSnapshot($invoice),
        ];
    }
}
