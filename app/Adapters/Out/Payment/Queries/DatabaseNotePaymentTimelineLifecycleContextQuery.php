<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment\Queries;

use Illuminate\Support\Facades\DB;

final class DatabaseNotePaymentTimelineLifecycleContextQuery
{
    /**
     * @param  list<array<string, mixed>>  $events
     * @return array<string, array{payable_total_rupiah:int,refunded_before_rupiah:int}>
     */
    public function byPaymentId(string $noteId, array $events): array
    {
        $fallbackTotal = (int) DB::table('notes')->where('id', $noteId)->value('total_rupiah');
        $revisions = DB::table('note_revisions')
            ->where('note_root_id', $noteId)
            ->orderBy('created_at')
            ->orderBy('revision_number')
            ->get(['grand_total_rupiah', 'created_at'])
            ->all();
        $refunds = $this->refunds($noteId);
        $contexts = [];
        foreach ($events as $event) {
            $occurredAt = (string) ($event['occurred_at'] ?? '');
            $contexts[(string) $event['payment_id']] = [
                'payable_total_rupiah' => $this->payableAt($revisions, $occurredAt, $fallbackTotal),
                'refunded_before_rupiah' => $this->refundedBefore($refunds, (string) $event['payment_id'], $occurredAt),
            ];
        }

        return $contexts;
    }

    /** @param list<object> $revisions */
    private function payableAt(array $revisions, string $occurredAt, int $fallbackTotal): int
    {
        $payable = $revisions === [] ? $fallbackTotal : (int) $revisions[0]->grand_total_rupiah;

        foreach ($revisions as $revision) {
            if ((string) $revision->created_at > $occurredAt) {
                break;
            }

            $payable = (int) $revision->grand_total_rupiah;
        }

        return max($payable, 0);
    }

    /** @return list<array{payment_id:?string,amount_rupiah:int,occurred_at:string}> */
    private function refunds(string $noteId): array
    {
        $customerRefunds = DB::table('customer_refunds')
            ->where('note_id', $noteId)
            ->get(['customer_payment_id', 'amount_rupiah', 'refunded_at', 'created_at'])
            ->map(static fn (object $row): array => [
                'payment_id' => (string) $row->customer_payment_id,
                'amount_rupiah' => (int) $row->amount_rupiah,
                'occurred_at' => trim((string) ($row->created_at ?? '')) !== ''
                    ? (string) $row->created_at
                    : (string) $row->refunded_at.' 00:00:00',
            ])
            ->all();

        $surplusRefunds = DB::table('note_revision_surplus_refund_payments')
            ->where('note_root_id', $noteId)
            ->where('status', 'active')
            ->get(['amount_rupiah', 'occurred_at'])
            ->map(static fn (object $row): array => [
                'payment_id' => null,
                'amount_rupiah' => (int) $row->amount_rupiah,
                'occurred_at' => (string) $row->occurred_at,
            ])
            ->all();

        return [...$customerRefunds, ...$surplusRefunds];
    }

    /**
     * @param  list<array{payment_id:?string,amount_rupiah:int,occurred_at:string}>  $refunds
     */
    private function refundedBefore(array $refunds, string $paymentId, string $occurredAt): int
    {
        $total = 0;

        foreach ($refunds as $refund) {
            if ($refund['payment_id'] === $paymentId || $refund['occurred_at'] > $occurredAt) {
                continue;
            }

            $total += max($refund['amount_rupiah'], 0);
        }

        return $total;
    }
}
