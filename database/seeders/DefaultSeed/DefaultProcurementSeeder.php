<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Database\Seeders\DefaultSeed\Support\DefaultSeedWindow;
use Database\Seeders\DefaultSeed\Support\SeedResultGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultProcurementSeeder extends Seeder
{
    private const SUPPLIERS = [
        'PT Astra Otoparts', 'PT FCC Indonesia', 'PT Exedy Indonesia', 'PT TDR Industries',
        'PT Bintang Racing Team', 'PT Federal Izumi', 'PT Musashi Auto Parts', 'PT Nissin Indonesia',
        'PT Denso Indonesia', 'PT Yamaha Parts', 'PT Suzuki Indomobil Parts', 'PT Kawasaki Motor Parts',
    ];

    public function run(CreateSupplierInvoiceFlowHandler $procurement): void
    {
        $products = DB::table('products')
            ->where('kode_barang', 'like', 'DEF-SP-%')
            ->orderBy('kode_barang')
            ->get(['id', 'harga_jual'])
            ->values();

        if ($products->count() !== 1000) {
            throw new RuntimeException('Default procurement requires exactly 1000 seeded products.');
        }

        $actorId = DefaultSeedActor::adminId();

        for ($index = 0; $index < 36; $index++) {
            $date = DefaultSeedWindow::dateAt($index, 36)->format('Y-m-d');
            $invoiceNo = sprintf('DEF-PROC-%s-%03d', str_replace('-', '', $date), $index + 1);

            if (DB::table('supplier_invoices')->where('nomor_faktur', $invoiceNo)->exists()) {
                continue;
            }

            $lines = [];
            for ($line = 0; $line < 3; $line++) {
                $product = $products[(($index * 29) + ($line * 131)) % $products->count()];
                $qty = 5 + (($index + ($line * 3)) % 16);
                $costPercent = 65 + (($index + $line) % 11);
                $unitCost = max(1000, (int) floor(((int) $product->harga_jual * $costPercent) / 100));
                $lines[] = [
                    'product_id' => (string) $product->id,
                    'qty_pcs' => $qty,
                    'line_total_rupiah' => $qty * $unitCost,
                ];
            }

            SeedResultGuard::data($procurement->handle(
                nomorFaktur: $invoiceNo,
                pt: self::SUPPLIERS[$index % count(self::SUPPLIERS)],
                tglKirim: $date,
                lines: $lines,
                autoRec: true,
                tglTerima: $date,
                performedByActorId: $actorId,
                performedByActorRole: 'admin',
                sourceChannel: 'seed_default',
            ), 'create procurement '.$invoiceNo);
        }
    }
}
