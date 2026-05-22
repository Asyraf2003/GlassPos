<?php

declare(strict_types=1);

namespace Database\Seeders\Transaction;

use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedDensity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerRefundBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $density = SeedDensity::baseline();
        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');

        $this->purgeSeededRefunds();

        $targets = $this->pickRefundTargets($density['refund_notes_per_month']);

        if ($targets->isEmpty()) {
            $this->command?->warn('CustomerRefundBaselineSeeder dilewati: target refund baseline tidak ditemukan.');
            return;
        }

        foreach ($targets->values() as $index => $target) {
            $paymentId = (string) $target->customer_payment_id;
            $noteId = (string) $target->note_id;
            $allocatedPairAmount = (int) $target->allocated_pair_amount;
            $refundAmount = $this->resolveRefundAmount($allocatedPairAmount, $index);
            $refundId = sprintf('seed-ref-bl-%s-%02d', str_replace('seed-pay-bl-', '', $paymentId), $index + 1);
            $refundedAt = CarbonImmutable::parse((string) $target->paid_at)->addDays(2)->format('Y-m-d');
            $reason = $this->resolveReason($index);

            DB::table('customer_refunds')->updateOrInsert(
                ['id' => $refundId],
                [
                    'customer_payment_id' => $paymentId,
                    'note_id' => $noteId,
                    'amount_rupiah' => $refundAmount,
                    'refunded_at' => $refundedAt,
                    'reason' => $reason,
                ]
            );

            $allocations = $this->allocateRefundAcrossComponents($refundId, $paymentId, $noteId, $refundAmount);

            foreach ($allocations as $allocation) {
                DB::table('refund_component_allocations')->updateOrInsert(
                    ['id' => $allocation['id']],
                    [
                        'customer_refund_id' => $allocation['customer_refund_id'],
                        'customer_payment_id' => $allocation['customer_payment_id'],
                        'note_id' => $allocation['note_id'],
                        'work_item_id' => $allocation['work_item_id'],
                        'component_type' => $allocation['component_type'],
                        'component_ref_id' => $allocation['component_ref_id'],
                        'refunded_amount_rupiah' => $allocation['refunded_amount_rupiah'],
                        'refund_priority' => $allocation['refund_priority'],
                    ]
                );
            }

            DB::table('audit_logs')->insert([
                'event' => 'customer_refund_recorded',
                'context' => json_encode([
                    'refund_id' => $refundId,
                    'customer_payment_id' => $paymentId,
                    'note_id' => $noteId,
                    'amount_rupiah' => $refundAmount,
                    'refunded_at' => $refundedAt,
                    'reason' => $reason,
                    'performed_by_actor_id' => $adminId,
                    'refund_allocation_count' => count($allocations),
                    'seed_source' => self::class,
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
        }

        $this->command?->info('CustomerRefundBaselineSeeder selesai: baseline refund 1 bulan dibuat.');
    }

    private function purgeSeededRefunds(): void
    {
        DB::table('refund_component_allocations')
            ->where('customer_refund_id', 'like', 'seed-ref-bl-%')
            ->delete();

        DB::table('customer_refunds')
            ->where('id', 'like', 'seed-ref-bl-%')
            ->delete();

        DB::table('audit_logs')
            ->where('event', 'customer_refund_recorded')
            ->where('context', 'like', '%seed-ref-bl-%')
            ->delete();
    }

    /**
     * @return Collection<int, object>
     */
    private function pickRefundTargets(int $limit): Collection
    {
        $rows = DB::table('payment_allocations as pa')
            ->join('customer_payments as cp', 'cp.id', '=', 'pa.customer_payment_id')
            ->join('notes as n', 'n.id', '=', 'pa.note_id')
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_allocated_note from payment_allocations group by note_id) as note_alloc'),
                'note_alloc.note_id',
                '=',
                'n.id'
            )
            ->select(
                'pa.customer_payment_id',
                'pa.note_id',
                'cp.paid_at',
                'pa.amount_rupiah as allocated_pair_amount',
                'n.total_rupiah',
                DB::raw('COALESCE(note_alloc.total_allocated_note, 0) as total_allocated_note')
            )
            ->where('pa.customer_payment_id', 'like', 'seed-pay-bl-%')
            ->orderBy('cp.paid_at')
            ->orderBy('pa.customer_payment_id')
            ->get();

        $full = $rows->filter(
            static fn (object $row): bool => (int) $row->total_allocated_note >= (int) $row->total_rupiah
        )->values();

        $partial = $rows->filter(
            static fn (object $row): bool => (int) $row->total_allocated_note < (int) $row->total_rupiah
        )->values();

        $targets = collect();

        if ($full->isNotEmpty()) {
            $targets->push($full->first());
        }

        if ($partial->isNotEmpty()) {
            $targets->push($partial->first());
        }

        foreach ($full->slice(1) as $row) {
            if ($targets->count() >= $limit) {
                break;
            }

            $targets->push($row);
        }

        foreach ($partial->slice(1) as $row) {
            if ($targets->count() >= $limit) {
                break;
            }

            $targets->push($row);
        }

        return $targets->unique(
            static fn (object $row): string => (string) $row->customer_payment_id.'::'.(string) $row->note_id
        )->take($limit)->values();
    }

    private function resolveRefundAmount(int $allocatedPairAmount, int $index): int
    {
        $basePercent = match ($index % 3) {
            0 => 15,
            1 => 20,
            default => 10,
        };

        $amount = max(1000, intdiv($allocatedPairAmount * $basePercent, 100));

        if ($amount >= $allocatedPairAmount) {
            return max(1000, $allocatedPairAmount - 1000);
        }

        return $amount;
    }

    private function resolveReason(int $index): string
    {
        return match ($index % 3) {
            0 => 'Koreksi baseline refund: penyesuaian biaya jasa',
            1 => 'Koreksi baseline refund: retur komponen sebagian',
            default => 'Koreksi baseline refund: penyesuaian transaksi pelanggan',
        };
    }

    /**
     * @return list<array{
     *   id:string,
     *   customer_refund_id:string,
     *   customer_payment_id:string,
     *   note_id:string,
     *   work_item_id:string,
     *   component_type:string,
     *   component_ref_id:string,
     *   refunded_amount_rupiah:int,
     *   refund_priority:int
     * }>
     */
    private function allocateRefundAcrossComponents(
        string $refundId,
        string $paymentId,
        string $noteId,
        int $refundAmount,
    ): array {
        $components = DB::table('payment_component_allocations')
            ->select(
                'work_item_id',
                'component_type',
                'component_ref_id',
                'allocated_amount_rupiah',
                'allocation_priority'
            )
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', $noteId)
            ->orderBy('allocation_priority')
            ->get();

        if ($components->isEmpty()) {
            throw new \RuntimeException(sprintf(
                'Payment component allocation tidak ditemukan untuk refund baseline payment [%s] note [%s].',
                $paymentId,
                $noteId
            ));
        }

        $refunded = DB::table('refund_component_allocations')
            ->select('component_type', 'component_ref_id', DB::raw('SUM(refunded_amount_rupiah) as total_refunded'))
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', $noteId)
            ->groupBy('component_type', 'component_ref_id')
            ->get();

        $refundedByComponent = [];
        foreach ($refunded as $row) {
            $key = $this->componentKey((string) $row->component_type, (string) $row->component_ref_id);
            $refundedByComponent[$key] = (int) $row->total_refunded;
        }

        $remaining = $refundAmount;
        $priority = 1;
        $allocations = [];

        foreach ($components as $component) {
            $componentType = (string) $component->component_type;
            $componentRefId = (string) $component->component_ref_id;
            $key = $this->componentKey($componentType, $componentRefId);
            $alreadyRefunded = $refundedByComponent[$key] ?? 0;
            $available = max((int) $component->allocated_amount_rupiah - $alreadyRefunded, 0);

            if ($available === 0) {
                continue;
            }

            $take = min($remaining, $available);

            if ($take <= 0) {
                break;
            }

            $allocations[] = [
                'id' => sprintf('seed-ref-comp-bl-%s-%02d', str_replace('seed-ref-bl-', '', $refundId), $priority),
                'customer_refund_id' => $refundId,
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => (string) $component->work_item_id,
                'component_type' => $componentType,
                'component_ref_id' => $componentRefId,
                'refunded_amount_rupiah' => $take,
                'refund_priority' => $priority,
            ];

            $refundedByComponent[$key] = $alreadyRefunded + $take;
            $remaining -= $take;
            $priority++;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException(sprintf(
                'Refund baseline [%s] untuk payment [%s] note [%s] tidak bisa dialokasikan penuh ke komponen payment.',
                $refundId,
                $paymentId,
                $noteId
            ));
        }

        return $allocations;
    }

    private function componentKey(string $componentType, string $componentRefId): string
    {
        return $componentType.'::'.$componentRefId;
    }
}
