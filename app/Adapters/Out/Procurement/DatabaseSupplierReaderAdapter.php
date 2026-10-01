<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\Supplier\Supplier;
use App\Ports\Out\Procurement\SupplierReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierReaderAdapter implements SupplierReaderPort
{
    public function getById(string $supplierId): ?Supplier
    {
        $row = DB::table('suppliers')
            ->select(['id', 'nama_pt_pengirim', 'nama_pt_pengirim_normalized', 'bank_name', 'bank_account_number'])
            ->where('id', $supplierId)
            ->first();

        if ($row === null) {
            return null;
        }

        return Supplier::rehydrate(
            (string) $row->id,
            (string) $row->nama_pt_pengirim,
            $row->bank_name,
            $row->bank_account_number,
        );
    }

    public function getByNormalizedNamaPtPengirim(string $namaPtPengirimNormalized): ?Supplier
    {
        $row = DB::table('suppliers')
            ->select(['id', 'nama_pt_pengirim', 'nama_pt_pengirim_normalized', 'bank_name', 'bank_account_number'])
            ->where('nama_pt_pengirim_normalized', $namaPtPengirimNormalized)
            ->first();

        if ($row === null) {
            return null;
        }

        return Supplier::rehydrate(
            (string) $row->id,
            (string) $row->nama_pt_pengirim,
            $row->bank_name,
            $row->bank_account_number,
        );
    }

    public function search(string $query, int $limit = 10): array
    {
        $normalizedQuery = $this->normalizeNamaPtPengirim($query);

        if ($normalizedQuery === '') {
            return [];
        }

        $rows = DB::table('suppliers')
            ->select(['id', 'nama_pt_pengirim', 'nama_pt_pengirim_normalized', 'bank_name', 'bank_account_number'])
            ->where(function ($builder) use ($query, $normalizedQuery): void {
                $builder
                    ->where('nama_pt_pengirim', 'like', '%' . trim($query) . '%')
                    ->orWhere('nama_pt_pengirim_normalized', 'like', '%' . $normalizedQuery . '%');
            })
            ->orderBy('nama_pt_pengirim')
            ->limit($limit)
            ->get();

        return $rows
            ->map(
                static fn (object $row): Supplier => Supplier::rehydrate(
                    (string) $row->id,
                    (string) $row->nama_pt_pengirim,
                    $row->bank_name,
                    $row->bank_account_number,
                )
            )
            ->all();
    }

    private function normalizeNamaPtPengirim(string $namaPtPengirim): string
    {
        $normalized = trim($namaPtPengirim);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return mb_strtolower($normalized);
    }
}
