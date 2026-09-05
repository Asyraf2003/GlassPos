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
    private const INVOICE_COUNT = 36;

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

        $baseLines = intdiv($products->count(), self::INVOICE_COUNT);
        $remainder = $products->count() % self::INVOICE_COUNT;
        $actorId = DefaultSeedActor::adminId();

        for ($index = 0; $index < self::INVOICE_COUNT; $index++) {
            $date = DefaultSeedWindow::dateAt($index, self::INVOICE_COUNT)->format('Y-m-d');
            $invoiceNo = sprintf('DEF-PROC-%s-%03d', str_replace('-', '', $date), $index + 1);
            $lineCount = $baseLines + ($index < $remainder ? 1 : 0);
            $start = ($index * $baseLines) + min($index, $remainder);

            if (DB::table('supplier_invoices')->where('nomor_faktur', $invoiceNo)->exists()) {
                continue;
            }

            $lines = [];
            for ($line = 0; $line < $lineCount; $line++) {
                $productIndex = $start + $line;
                $product = $products[$productIndex];
                $qty = 12 + (($productIndex * 7) % 29);
                $costPercent = 65 + ($productIndex % 11);
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

        $withoutStock = DB::table('products as products')
            ->leftJoin('product_inventory as inventory', 'inventory.product_id', '=', 'products.id')
            ->where('products.kode_barang', 'like', 'DEF-SP-%')
            ->where(fn ($query) => $query
                ->whereNull('inventory.product_id')
                ->orWhere('inventory.qty_on_hand', '<=', 0))
            ->count();

        if ($withoutStock !== 0) {
            throw new RuntimeException("Default procurement left {$withoutStock} products without positive stock.");
        }
    }
}
