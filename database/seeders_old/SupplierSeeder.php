<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Procurement\Supplier\Supplier;
use App\Ports\Out\Procurement\SupplierWriterPort;
use App\Ports\Out\UuidPort;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SupplierSeeder extends Seeder
{
    public function run(SupplierWriterPort $writer, UuidPort $uuid): void
    {
        $supplierNames = [
            'PT. Astra Otoparts (Clutch Div)',
            'PT. FCC Indonesia',
            'PT. Exedy Manufacturing Indonesia',
            'PT. Faito Racing Indonesia',
            'PT. TDR Industries',
            'PT. Kawahara Racing',
            'PT. Bintang Racing Team (BRT)',
            'PT. Chemco Harapan Nusantara',
            'PT. Dirgaputra Eta Sembada',
            'PT. Daido Indonesia Manufacturing',
            'PT. Federal Izumi Manufacturing',
            'PT. Showa Indonesia Manufacturing',
            'PT. Musashi Auto Parts Indonesia',
            'PT. Nissin Kogyo Indonesia',
            'PT. Akebono Brake Astra Indonesia',
            'PT. TPR Indonesia',
            'PT. Mikuni Indonesia',
            'PT. Keihin Indonesia',
            'PT. Denso Indonesia',
            'PT. Yamaha Indonesia Motor Mfg (Parts)',
            'PT. Nusantara Motor Parts Sejahtera',
            'PT. Sinar Teknik Otomotif Abadi',
            'PT. Prima Sparepart Niaga',
            'PT. Cipta Komponen Kendara',
            'PT. Sentra Distribusi Partindo',
        ];

        foreach ($supplierNames as $supplierName) {
            $supplier = Supplier::create($uuid->generate(), $supplierName);

            $exists = DB::table('suppliers')
                ->where('nama_pt_pengirim_normalized', $supplier->namaPtPengirimNormalized())
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $writer->create($supplier);
        }
    }
}
