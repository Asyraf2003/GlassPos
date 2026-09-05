<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Application\Procurement\DTO\SupplierTableQuery;
use Illuminate\Database\Query\Builder;

trait SupplierTableFilters
{
    private function applyTableFilters(Builder $query, SupplierTableQuery $filters): Builder
    {
        if ($filters->q() !== null) {
            $keyword = $filters->q();

            $query->where('supplier_list_projection.nama_pt_pengirim', 'like', '%' . $keyword . '%');
        }

        if ($filters->status() === 'outstanding') {
            $query->where('supplier_list_projection.outstanding_rupiah', '>', 0);
        } elseif ($filters->status() === 'settled') {
            $query->where('supplier_list_projection.outstanding_rupiah', '=', 0);
        }

        return $query;
    }
}
