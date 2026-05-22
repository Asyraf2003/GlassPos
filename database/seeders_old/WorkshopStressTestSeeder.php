<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Note\UseCases\{CreateNoteHandler, AddWorkItemHandler};
use App\Application\Payment\UseCases\{RecordCustomerPaymentHandler, AllocateCustomerPaymentHandler, RecordCustomerRefundHandler};
use App\Application\Procurement\UseCases\{CreateSupplierInvoiceHandler, ReceiveSupplierInvoiceHandler};
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WorkshopStressTestSeeder extends Seeder
{
    public function run(
        CreateNoteHandler $createNote,
        AddWorkItemHandler $addItem,
        RecordCustomerPaymentHandler $recordPayment,
        AllocateCustomerPaymentHandler $allocatePayment,
        CreateSupplierInvoiceHandler $createInvoice,
        ReceiveSupplierInvoiceHandler $receiveInvoice,
        RecordCustomerRefundHandler $recordRefund
    ): void {
        $products = DB::table('products')->get();
        $admin = DB::table('users')->where('email', 'admin@gmail.com')->first();
        $kasir = DB::table('users')->where('email', 'kasir@gmail.com')->first();

        if ($products->isEmpty()) {
            $this->command->error("Gagal: Tabel products kosong!");
            return;
        }
        if (!$admin) {
            $this->command->error("Gagal: Admin admin@gmail.com tidak ditemukan!");
            return;
        }
        if (!$kasir) {
            $this->command->error("Gagal: Kasir kasir@gmail.com tidak ditemukan!");
            return;
        }

        $this->command->info("Prasyarat OK. Menjalankan stress test untuk " . $products->count() . " produk.");

        // 1. Otorisasi Capability (Grounded: column 'active')
        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string)$admin->id],
            ['active' => true]
        );

        $totalDays = 30;
        $txPerDay = 50;
        $bar = $this->command->getOutput()->createProgressBar($totalDays);
        $bar->start();

        for ($i = $totalDays; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            // 2. PROCUREMENT CYCLE
            if ($i % 3 === 0) {
                $this->procurementFullCycle($createInvoice, $receiveInvoice, $products, $date);
            }

            // 3. WORKSHOP CYCLE
            for ($j = 0; $j < $txPerDay; $j++) {
                $this->workshopFullCycle(
                    $createNote, $addItem, $recordPayment, $allocatePayment, $recordRefund,
                    (string)$kasir->id, (string)$admin->id, $products, $date
                );
            }
            $bar->advance();
        }
        $bar->finish();
        $this->command->getOutput()->writeln("");
    }

    private function workshopFullCycle($createNote, $addItem, $recordPayment, $allocatePayment, $recordRefund, $kasirId, $adminId, $products, $date): void
    {
        $resNote = $createNote->handle('Customer ' . rand(1, 2000), null, $date);
        if ($resNote->isFailure()) return;
        $noteId = $resNote->data()['id'];

        foreach (range(1, rand(1, 3)) as $k) {
            $p = $products->random();
            $type = [WorkItem::TYPE_SERVICE_ONLY, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE][rand(0, 2)];
            
            $addItem->handle(
                nId: $noteId, lNo: $k, type: $type,
                sd: ['service_name' => 'Jasa Perbaikan ' . $k, 'service_price_rupiah' => rand(3, 10) * 10000],
                ext: ($type === WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE) ? [['cost_description' => 'Baut/Seal Luar', 'unit_cost_rupiah' => 5000, 'qty' => 1]] : [],
                sto: (in_array($type, [WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::TYPE_STORE_STOCK_SALE_ONLY])) ? 
                     [['product_id' => $p->id, 'qty' => 1, 'line_total_rupiah' => $p->harga_jual]] : []
            );
        }

        // Ambil total_rupiah (Grounded: column 'total_rupiah')
        $total = DB::table('notes')->where('id', $noteId)->value('total_rupiah');
        if ($total > 0 && rand(1, 100) <= 85) {
            $resPay = $recordPayment->handle((int)$total, $date);
            if ($resPay->isSuccess()) {
                $payId = $resPay->data()['payment']['id'];
                $allocatePayment->handle($payId, $noteId, (int)$total);

                // 4. CUSTOMER REFUND (Grounded: columns match RecordCustomerRefundHandler)
                if (rand(1, 1000) <= 15) {
                    $recordRefund->handle($payId, $noteId, 25000, $date, 'Retur Barang', $adminId);
                }
            }
        }
    }

    private function procurementFullCycle($createInvoice, $receiveInvoice, $products, $date): void
    {
        $suppliers = ['PT. Astra Otoparts', 'PT. KYB Indonesia'];
        $selected = $products->random(rand(5, 10));
        
        $lines = $selected->map(fn($p) => [
            'product_id' => $p->id, 
            'qty_pcs' => 100, 
            'line_total_rupiah' => (int)(100 * ($p->harga_jual * 0.6))
        ])->toArray();

        $resInv = $createInvoice->handle($suppliers[array_rand($suppliers)], $date, $lines);
        
        if ($resInv->isSuccess()) {
            $invId = $resInv->data()['id'];
            $invLines = DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $invId)->get();
            
            $receiveInvoice->handle($invId, $date, $invLines->map(fn($l) => [
                'supplier_invoice_line_id' => $l->id, 
                'qty_diterima' => $l->qty_pcs
            ])->toArray());

            // 5. SUPPLIER PAYMENT (Grounded: columns id, supplier_invoice_id, amount_rupiah, paid_at, proof_status)
            DB::table('supplier_payments')->insert([
                'id' => Str::uuid()->toString(),
                'supplier_invoice_id' => $invId,
                'amount_rupiah' => (int)$invLines->sum('line_total_rupiah'),
                'paid_at' => $date,
                'proof_status' => 'pending'
            ]);
        }
    }
}
