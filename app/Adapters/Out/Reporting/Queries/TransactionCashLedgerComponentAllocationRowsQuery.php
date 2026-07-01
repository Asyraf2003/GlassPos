<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerComponentAllocationRowsQuery
{
    public function query(string $fromEventDate, string $toEventDate): Builder
    {
        return DB::table('payment_component_allocations')
            ->join('customer_payments', 'customer_payments.id', '=', 'payment_component_allocations.customer_payment_id')
            ->leftJoin(
                'customer_payment_cash_details',
                'customer_payment_cash_details.customer_payment_id',
                '=',
                'customer_payments.id',
            )
            ->leftJoin('notes', 'notes.id', '=', 'payment_component_allocations.note_id')
            ->whereBetween('customer_payments.paid_at', [$fromEventDate, $toEventDate])
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_allocations')
                    ->whereColumn('payment_allocations.customer_payment_id', 'payment_component_allocations.customer_payment_id')
                    ->whereColumn('payment_allocations.note_id', 'payment_component_allocations.note_id');
            })
            ->groupBy(
                'payment_component_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_payments.paid_at',
                'customer_payments.payment_method',
                'payment_component_allocations.customer_payment_id',
                'customer_payment_cash_details.amount_paid_rupiah',
                'customer_payment_cash_details.amount_received_rupiah',
                'customer_payment_cash_details.change_rupiah',
            )
            ->select([
                'payment_component_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                DB::raw('customer_payments.paid_at as event_date'),
                DB::raw('SUM(payment_component_allocations.allocated_amount_rupiah) as event_amount_rupiah'),
                'payment_component_allocations.customer_payment_id',
                DB::raw("COALESCE(NULLIF(customer_payments.payment_method, ''), 'unknown') as payment_method"),
                'customer_payment_cash_details.amount_paid_rupiah as cash_amount_paid_rupiah',
                'customer_payment_cash_details.amount_received_rupiah as cash_amount_received_rupiah',
                'customer_payment_cash_details.change_rupiah as cash_change_rupiah',
                DB::raw("'payment_component_allocations' as source_table"),
            ]);
    }
}
