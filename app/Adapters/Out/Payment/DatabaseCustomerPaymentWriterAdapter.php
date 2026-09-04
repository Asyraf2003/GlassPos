<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment;

use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Payment\CustomerPayment\CustomerPaymentCashDetail;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseCustomerPaymentWriterAdapter implements CustomerPaymentWriterPort
{
    public function create(CustomerPayment $customerPayment, ?CustomerPaymentCashDetail $cashDetail = null): void
    {
        $clock = now();
        $now = $clock->format('Y-m-d H:i:s');

        DB::table('customer_payments')->insert([
            'id' => $customerPayment->id(),
            'amount_rupiah' => $customerPayment->amountRupiah()->amount(),
            'payment_method' => $customerPayment->paymentMethod(),
            'paid_at' => $customerPayment->paidAt()->format('Y-m-d'),
            'recorded_at' => $clock->format('Y-m-d H:i:s.u'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($cashDetail === null) {
            return;
        }

        DB::table('customer_payment_cash_details')->insert([
            'customer_payment_id' => $cashDetail->customerPaymentId(),
            'amount_paid_rupiah' => $cashDetail->amountPaidRupiah()->amount(),
            'amount_received_rupiah' => $cashDetail->amountReceivedRupiah()->amount(),
            'change_rupiah' => $cashDetail->changeRupiah()->amount(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
