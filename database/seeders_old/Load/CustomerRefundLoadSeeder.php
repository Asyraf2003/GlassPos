<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedDensity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerRefundLoadSeeder extends Seeder
{
    public function run(): void
    {
        $density = SeedDensity::monster();
        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');

        $this->purgeSeededRefunds();

        $targets = $this->pickRefundTargets((int) $density['refund_paid_note_percent']);

        if ($targets->isEmpty()) {
            $this->command?->warn('CustomerRefundLoadSeeder dilewati: target refund monster tidak ditemukan.');
            return;
        }

        foreach ($targets->values() as $index => $target) {
            $paymentId = (string) $target->customer_payment_id;
            $noteId = (string) $target->note_id;
            $allocatedPairAmount = (int) $target->allocated_pair_amount;
            $refundAmount = $this->resolveRefundAmount($allocatedPairAmount, $index);
            $refundId = sprintf('seed-ref-load-%s-%02d', str_replace('seed-pay-load-', '', $paymentId), $index + 1);
            $refundedAt = $this->resolveRefundedAt((string) $target->paid_at);
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

        $this->command?->info('CustomerRefundLoadSeeder selesai: refund monster 1 tahun dibuat.');
    }

    private function purgeSeededRefunds(): void
    {
        DB::table('refund_component_allocations')
            ->where('customer_refund_id', 'like', 'seed-ref-load-%')
            ->delete();

        DB::table('customer_refunds')
            ->where('id', 'like', 'seed-ref-load-%')
            ->delete();

        DB::table('audit_logs')
            ->where('event', 'customer_refund_recorded')
            ->where('context', 'like', '%seed-ref-load-%')
            ->delete();
    }

    /**
     * @return Collection<int, object>
     */
    private function pickRefundTargets(int $refundPercent): Collection
    {
        $rows = DB::table('payment_allocations as pa')
            ->join('customer_payments as cp', 'cp.id', '=', 'pa.customer_payment_id')
            ->join('notes as n', 'n.id', '=', 'pa.note_id')
            ->select(
                'pa.customer_payment_id',
                'pa.note_id',
                'cp.paid_at',
                'pa.amount_rupiah as allocated_pair_amount',
                'n.transaction_date'
            )
            ->where('pa.customer_payment_id', 'like', 'seed-pay-load-%')
            ->orderBy('cp.paid_at')
            ->orderBy('pa.customer_payment_id')
            ->get()
            ->values();

        if ($rows->isEmpty()) {
            return collect();
        }

        $targetCount = max(1, (int) ceil($rows->count() * ($refundPercent / 100)));
        $step = max(1, (int) floor($rows->count() / $targetCount));

        $targets = collect();

        foreach ($rows as $index => $row) {
            if (($index % $step) === 0) {
                $targets->push($row);
            }

            if ($targets->count() >= $targetCount) {
                break;
            }
        }

        return $targets->unique(
            static fn (object $row): string => (string) $row->customer_payment_id.'::'.(string) $row->note_id
        )->take($targetCount)->values();
    }

    private function resolveRefundAmount(int $allocatedPairAmount, int $index): int
    {
        $percent = match ($index % 4) {
            0 => 10,
            1 => 12,
            2 => 15,
            default => 8,
        };

        $amount = max(1000, intdiv($allocatedPairAmount * $percent, 100));

        if ($amount >= $allocatedPairAmount) {
            return max(1000, $allocatedPairAmount - 1000);
        }

        return $amount;
    }

    private function resolveRefundedAt(string $paidAt): string
    {
        $paid = CarbonImmutable::parse($paidAt);
        $candidate = $paid->addDays(3);
        $today = CarbonImmutable::today('Asia/Jakarta');

        return $candidate->greaterThan($today)
            ? $today->format('Y-m-d')
            : $candidate->format('Y-m-d');
    }

    private function resolveReason(int $index): string
    {
        return match ($index % 4) {
            0 => 'Seed monster refund: penyesuaian jasa',
            1 => 'Seed monster refund: retur komponen sebagian',
            2 => 'Seed monster refund: goodwill pelanggan',
            default => 'Seed monster refund: koreksi transaksi',
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
                'Payment component allocation tidak ditemukan untuk refund load payment [%s] note [%s].',
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
                'id' => sprintf('seed-ref-comp-load-%s-%02d', str_replace('seed-ref-load-', '', $refundId), $priority),
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
                'Refund load [%s] untuk payment [%s] note [%s] tidak bisa dialokasikan penuh ke komponen payment.',
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
