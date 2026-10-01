<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

trait SupplierTableBaseQuery
{
    private function baseTableQuery(): Builder
    {
        return DB::table('supplier_list_projection')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_list_projection.supplier_id')
            ->select([
                'supplier_id as id',
                'supplier_list_projection.nama_pt_pengirim',
                'suppliers.bank_name',
                'suppliers.bank_account_number',
                'invoice_count',
                'outstanding_rupiah',
                'invoice_unpaid_count',
                'last_shipment_date',
            ]);
    }
}
