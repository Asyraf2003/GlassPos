<?php

declare(strict_types=1);

namespace Tests\Support\Procurement;

use Illuminate\Support\Facades\DB;

trait CurrentSupplierPayableFixture
{
    use SupplierPayableReminderFixtures;

    private function oldInvoice(string $key = 'august', int $total = 10000000): string
    {
        $id = $this->seedSupplierPayableInvoice($key, '2026-08-20', $total, 'Supplier '.$key);
        DB::table('supplier_invoices')->where('id', $id)->update(['tanggal_pengiriman' => '2026-08-10']);

        return $id;
    }

    private function reversePayment(string $id): void
    {
        DB::table('supplier_payment_reversals')->insert([
            'id' => 'reversal-'.$id,
            'supplier_payment_id' => $id,
            'reason' => 'Current payable reversal test',
            'performed_by_actor_id' => 'actor-test',
            'created_at' => '2026-09-01 10:00:00',
            'updated_at' => '2026-09-01 10:00:00',
        ]);
    }
}
