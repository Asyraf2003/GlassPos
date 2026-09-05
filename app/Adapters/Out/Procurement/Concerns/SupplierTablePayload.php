<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement\Concerns;

use App\Application\Procurement\DTO\SupplierTableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait SupplierTablePayload
{
    /**
     * @return array{
     *   rows:list<array<string, int|string|null>>,
     *   meta:array<string, mixed>
     * }
     */
    private function toTablePayload(LengthAwarePaginator $paginator, SupplierTableQuery $query): array
    {
        $rows = array_map(static fn (object $row): array => [
            'id' => (string) $row->id,
            'nama_pt_pengirim' => (string) $row->nama_pt_pengirim,
            'invoice_count' => (int) $row->invoice_count,
            'outstanding_rupiah' => (int) $row->outstanding_rupiah,
            'invoice_unpaid_count' => (int) $row->invoice_unpaid_count,
            'last_shipment_date' => $row->last_shipment_date !== null
                ? (string) $row->last_shipment_date
                : null,
        ], $paginator->items());

        return [
            'rows' => $rows,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy(),
                'sort_dir' => $query->sortDir(),
                'filters' => [
                    'q' => $query->q(),
                    'status' => $query->status(),
                ],
            ],
        ];
    }
}
