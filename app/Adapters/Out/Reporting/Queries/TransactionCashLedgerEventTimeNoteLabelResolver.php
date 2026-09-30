<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerEventTimeNoteLabelResolver
{
    /**
     * @param list<array{
     *   key:string,
     *   note_id:string,
     *   occurred_at:string,
     *   fallback_customer_name:string,
     *   fallback_transaction_date:string,
     *   event_date:string
     * }> $events
     * @return array<string, string>
     */
    public function resolve(array $events): array
    {
        if ($events === []) {
            return [];
        }

        $noteIds = array_values(array_unique(array_column($events, 'note_id')));
        $revisions = DB::table('note_revisions')
            ->whereIn('note_root_id', $noteIds)
            ->orderBy('note_root_id')
            ->orderBy('created_at')
            ->orderBy('revision_number')
            ->get([
                'note_root_id',
                'customer_name',
                'transaction_date',
                'created_at',
                'revision_number',
            ])
            ->groupBy('note_root_id');

        $labels = [];

        foreach ($events as $event) {
            $noteRevisions = $revisions->get($event['note_id']);
            $activeRevision = $noteRevisions?->first();

            if ($noteRevisions !== null) {
                foreach ($noteRevisions as $revision) {
                    if ((string) $revision->created_at > $event['occurred_at']) {
                        break;
                    }

                    $activeRevision = $revision;
                }
            }

            $customerName = $activeRevision !== null
                ? (string) $activeRevision->customer_name
                : $event['fallback_customer_name'];
            $transactionDate = $activeRevision !== null
                ? (string) $activeRevision->transaction_date
                : $event['fallback_transaction_date'];

            $labels[$event['key']] = $this->label(
                $customerName,
                $transactionDate,
                $event['event_date'],
            );
        }

        return $labels;
    }

    private function label(string $customerName, string $transactionDate, string $eventDate): string
    {
        $customerName = trim($customerName);
        $date = trim($transactionDate) !== '' ? $transactionDate : $eventDate;

        return $customerName !== ''
            ? $customerName.' · '.$date
            : 'Nota '.$date;
    }
}
