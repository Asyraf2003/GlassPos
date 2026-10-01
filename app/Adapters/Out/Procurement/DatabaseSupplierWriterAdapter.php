<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\Supplier\Supplier;
use App\Ports\Out\Procurement\SupplierWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierWriterAdapter implements SupplierWriterPort
{
    public function create(Supplier $supplier): void
    {
        DB::table('suppliers')->insert($this->toRecord($supplier));
    }

    public function update(Supplier $supplier): void
    {
        DB::table('suppliers')
            ->where('id', $supplier->id())
            ->update($this->toRecord($supplier));
    }

    /**
     * @return array<string, string|null>
     */
    private function toRecord(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id(),
            'bank_name' => $supplier->bankName(),
            'bank_account_number' => $supplier->bankAccountNumber(),
            'nama_pt_pengirim' => $supplier->namaPtPengirim(),
            'nama_pt_pengirim_normalized' => $supplier->namaPtPengirimNormalized(),
        ];
    }
}
