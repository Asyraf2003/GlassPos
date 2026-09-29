<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionSummaryRefundDueTotalsQuery
{
    public function query(?string $asOfDate = null): Builder
    {
        return DB::table('note_revision_surplus_dispositions')
            ->selectRaw('note_root_id as note_id, SUM(amount_rupiah) as refund_due_rupiah')
            ->where('disposition_type', 'refund_due')
            ->where('status', 'active')
            ->when($asOfDate !== null, fn (Builder $query) => $query->where('occurred_at', '<=', $asOfDate.' 23:59:59'))
            ->groupBy('note_root_id');
    }
}
