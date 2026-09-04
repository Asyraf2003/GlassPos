<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment\Queries;

use Illuminate\Support\Facades\DB;

final class DatabaseNotePaymentTimelineAllocationAmountsQuery
{
    /** @return array<string, int> */
    public function byPaymentId(string $noteId): array
    {
        $amounts = [];
        $componentRows = DB::table('payment_component_allocations')
            ->where('note_id', $noteId)
            ->groupBy('customer_payment_id')
            ->get([
                'customer_payment_id',
                DB::raw('SUM(allocated_amount_rupiah) as allocated_amount_rupiah'),
            ]);

        foreach ($componentRows as $row) {
            $amounts[(string) $row->customer_payment_id] = (int) $row->allocated_amount_rupiah;
        }

        $legacyQuery = DB::table('payment_allocations')
            ->where('note_id', $noteId)
            ->groupBy('customer_payment_id');

        if ($amounts !== []) {
            $legacyQuery->whereNotIn('customer_payment_id', array_keys($amounts));
        }

        foreach ($legacyQuery->get([
            'customer_payment_id',
            DB::raw('SUM(amount_rupiah) as allocated_amount_rupiah'),
        ]) as $row) {
            $amounts[(string) $row->customer_payment_id] = (int) $row->allocated_amount_rupiah;
        }

        return $amounts;
    }
}
