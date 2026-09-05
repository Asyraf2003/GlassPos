<?php

declare(strict_types=1);

namespace App\Adapters\Out\Payment;

use App\Adapters\Out\Payment\Queries\DatabaseNotePaymentTimelineAllocationAmountsQuery;
use App\Adapters\Out\Payment\Queries\DatabaseNotePaymentTimelineLifecycleContextQuery;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Payment\NotePaymentTimelineReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNotePaymentTimelineReaderAdapter implements NotePaymentTimelineReaderPort
{
    public function __construct(
        private readonly DatabaseNotePaymentTimelineAllocationAmountsQuery $allocations,
        private readonly DatabaseNotePaymentTimelineLifecycleContextQuery $lifecycleContext,
    ) {}

    public function findByNoteId(string $noteId): array
    {
        $noteId = trim($noteId);
        if ($noteId === '') {
            throw new DomainException('Note id pada payment timeline wajib ada.');
        }

        $amounts = $this->allocations->byPaymentId($noteId);
        if ($amounts === []) {
            return [];
        }

        $events = DB::table('customer_payments')
            ->leftJoin(
                'customer_payment_cash_details',
                'customer_payment_cash_details.customer_payment_id',
                '=',
                'customer_payments.id',
            )
            ->whereIn('customer_payments.id', array_keys($amounts))
            ->get([
                'customer_payments.id',
                'customer_payments.amount_rupiah',
                'customer_payments.payment_method',
                'customer_payments.paid_at',
                'customer_payments.recorded_at',
                'customer_payments.created_at',
                'customer_payment_cash_details.amount_received_rupiah',
                'customer_payment_cash_details.change_rupiah',
            ])
            ->map(fn (object $row): array => $this->map($row, $amounts))
            ->all();

        usort($events, static fn (array $left, array $right): int => [$left['occurred_at'], $left['payment_id']] <=> [$right['occurred_at'], $right['payment_id']]
        );

        $contexts = $this->lifecycleContext->byPaymentId($noteId, $events);

        foreach ($events as &$event) {
            $event += $contexts[$event['payment_id']] ?? [
                'payable_total_rupiah' => 0,
                'refunded_before_rupiah' => 0,
            ];
        }
        unset($event);

        return $events;
    }

    /** @param array<string, int> $amounts */
    private function map(object $row, array $amounts): array
    {
        $paymentId = (string) $row->id;
        $paidAt = (string) $row->paid_at;
        $recordedAt = trim((string) ($row->recorded_at ?? ''));
        $createdAt = trim((string) ($row->created_at ?? ''));

        return [
            'payment_id' => $paymentId,
            'payment_amount_rupiah' => (int) $row->amount_rupiah,
            'allocated_amount_rupiah' => (int) ($amounts[$paymentId] ?? 0),
            'payment_method' => (string) ($row->payment_method ?? 'unknown'),
            'paid_at' => $paidAt,
            'occurred_at' => $recordedAt !== ''
                ? $recordedAt
                : ($createdAt !== '' ? $createdAt : $paidAt.' 00:00:00'),
            'amount_received_rupiah' => $this->nullableInt($row->amount_received_rupiah),
            'change_rupiah' => $this->nullableInt($row->change_rupiah),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
