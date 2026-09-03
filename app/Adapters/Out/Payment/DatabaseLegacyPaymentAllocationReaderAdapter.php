<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment;

use App\Ports\Out\Payment\LegacyPaymentAllocationReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseLegacyPaymentAllocationReaderAdapter implements LegacyPaymentAllocationReaderPort
{
    public function listWithoutComponentAllocations(string $noteId): array
    {
        $componentPaymentIds = DB::table('payment_component_allocations')
            ->where('note_id', trim($noteId))
            ->select('customer_payment_id');

        return DB::table('payment_allocations')
            ->where('note_id', trim($noteId))
            ->whereNotIn('customer_payment_id', $componentPaymentIds)
            ->orderBy('id')
            ->get(['id', 'customer_payment_id', 'amount_rupiah'])
            ->all();
    }
}
