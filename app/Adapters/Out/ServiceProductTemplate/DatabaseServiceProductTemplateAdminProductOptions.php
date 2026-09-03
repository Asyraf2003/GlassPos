<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use Illuminate\Support\Facades\DB;

trait DatabaseServiceProductTemplateAdminProductOptions
{
    /** @return list<array{id:string,label:string}> */
    public function productOptions(): array
    {
        return DB::table('products')
            ->whereNull('deleted_at')
            ->select(['id', 'kode_barang', 'nama_barang', 'harga_jual'])
            ->orderBy('nama_barang')
            ->orderBy('kode_barang')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'label' => $this->productLabel($row),
            ])
            ->all();
    }

    private function productLabel(object $row): string
    {
        return trim(sprintf(
            '%s%s · Harga jual %s',
            $row->kode_barang !== null && $row->kode_barang !== '' ? (string) $row->kode_barang.' - ' : '',
            (string) $row->nama_barang,
            number_format((int) $row->harga_jual, 0, ',', '.'),
        ));
    }
}
