<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedDensity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CustomerPaymentLoadSeeder extends Seeder
{
    public function run(): void
    {
        $density = SeedDensity::monster();

        $notes = DB::table('notes')
            ->select('id', 'transaction_date', 'total_rupiah')
            ->where('id', 'like', 'seed-note-load-%')
            ->where('total_rupiah', '>', 0)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->values();

        if ($notes->isEmpty()) {
            $this->command?->warn('CustomerPaymentLoadSeeder dilewati: transaksi monster belum tersedia.');
            return;
        }

        $this->purgeSeededPayments();

        $distribution = $density['payment_distribution'];

        foreach ($notes as $index => $note) {
            $pattern = $index % 20;
            $noteId = (string) $note->id;
            $noteDate = CarbonImmutable::parse((string) $note->transaction_date);
            $total = (int) $note->total_rupiah;

            $fullThreshold = (int) ($distribution['full'] / 5);
            $partialThreshold = $fullThreshold + (int) ($distribution['partial'] / 5);

            if ($pattern < $fullThreshold) {
                if ($pattern === 0) {
                    $first = max(1000, intdiv($total * 35, 100));
                    $second = max(1000, intdiv($total * 30, 100));
                    $third = $total - $first - $second;

                    $this->createPayment(
                        paymentId: $this->paymentId($noteId, 1),
                        noteId: $noteId,
                        allocationId: $this->allocationId($noteId, 1),
                        paidAt: $noteDate->format('Y-m-d'),
                        amount: $first,
                    );

                    $this->createPayment(
                        paymentId: $this->paymentId($noteId, 2),
                        noteId: $noteId,
                        allocationId: $this->allocationId($noteId, 2),
                        paidAt: $noteDate->addDay()->format('Y-m-d'),
                        amount: $second,
                    );

                    $this->createPayment(
                        paymentId: $this->paymentId($noteId, 3),
                        noteId: $noteId,
                        allocationId: $this->allocationId($noteId, 3),
                        paidAt: $noteDate->addDays(2)->format('Y-m-d'),
                        amount: max(1000, $third),
                    );

                    continue;
                }

                if ($pattern === 1 || $pattern === 2) {
                    $first = max(1000, intdiv($total * 45, 100));
                    $second = $total - $first;

                    $this->createPayment(
                        paymentId: $this->paymentId($noteId, 1),
                        noteId: $noteId,
                        allocationId: $this->allocationId($noteId, 1),
                        paidAt: $noteDate->format('Y-m-d'),
                        amount: $first,
                    );

                    $this->createPayment(
                        paymentId: $this->paymentId($noteId, 2),
                        noteId: $noteId,
                        allocationId: $this->allocationId($noteId, 2),
                        paidAt: $noteDate->addDay()->format('Y-m-d'),
                        amount: $second,
                    );

                    continue;
                }

                $this->createPayment(
                    paymentId: $this->paymentId($noteId, 1),
                    noteId: $noteId,
                    allocationId: $this->allocationId($noteId, 1),
                    paidAt: $noteDate->format('Y-m-d'),
                    amount: $total,
                );

                continue;
            }

            if ($pattern < $partialThreshold) {
                $partial = max(1000, intdiv($total * $this->partialPercentForPattern($pattern), 100));

                if ($partial >= $total) {
                    $partial = max(1000, $total - 1000);
                }

                $this->createPayment(
                    paymentId: $this->paymentId($noteId, 1),
                    noteId: $noteId,
                    allocationId: $this->allocationId($noteId, 1),
                    paidAt: $noteDate->format('Y-m-d'),
                    amount: $partial,
                );

                continue;
            }

            // unpaid
        }

        $this->command?->info('CustomerPaymentLoadSeeder selesai: payment monster 1 tahun dibuat.');
    }

    private function partialPercentForPattern(int $pattern): int
    {
        return match ($pattern % 3) {
            0 => 40,
            1 => 55,
            default => 70,
        };
    }

    private function purgeSeededPayments(): void
    {
        DB::table('refund_component_allocations')
            ->where('customer_refund_id', 'like', 'seed-ref-load-%')
            ->delete();

        DB::table('customer_refunds')
            ->where('id', 'like', 'seed-ref-load-%')
            ->orWhere('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('payment_component_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('payment_allocations')
            ->where('customer_payment_id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('customer_payments')
            ->where('id', 'like', 'seed-pay-load-%')
            ->delete();

        DB::table('audit_logs')
            ->where('event', 'payment_allocated')
            ->where('context', 'like', '%seed-pay-load-%')
            ->delete();
    }

    private function createPayment(
        string $paymentId,
        string $noteId,
        string $allocationId,
        string $paidAt,
        int $amount,
    ): void {
        DB::table('customer_payments')->updateOrInsert(
            ['id' => $paymentId],
            [
                'amount_rupiah' => $amount,
                'paid_at' => $paidAt,
            ]
        );

        DB::table('payment_allocations')->updateOrInsert(
            ['id' => $allocationId],
            [
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'amount_rupiah' => $amount,
            ]
        );

        $componentAllocations = $this->allocateAcrossComponents($paymentId, $noteId, $amount);

        foreach ($componentAllocations as $allocation) {
            DB::table('payment_component_allocations')->updateOrInsert(
                ['id' => $allocation['id']],
                [
                    'customer_payment_id' => $allocation['customer_payment_id'],
                    'note_id' => $allocation['note_id'],
                    'work_item_id' => $allocation['work_item_id'],
                    'component_type' => $allocation['component_type'],
                    'component_ref_id' => $allocation['component_ref_id'],
                    'component_amount_rupiah_snapshot' => $allocation['component_amount_rupiah_snapshot'],
                    'allocated_amount_rupiah' => $allocation['allocated_amount_rupiah'],
                    'allocation_priority' => $allocation['allocation_priority'],
                ]
            );
        }

        DB::table('audit_logs')->insert([
            'event' => 'payment_allocated',
            'context' => json_encode([
                'payment_id' => $paymentId,
                'note_id' => $noteId,
                'amount' => $amount,
                'allocation_count' => count($componentAllocations),
                'seed_source' => self::class,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return list<array{
     *   id:string,
     *   customer_payment_id:string,
     *   note_id:string,
     *   work_item_id:string,
     *   component_type:string,
     *   component_ref_id:string,
     *   component_amount_rupiah_snapshot:int,
     *   allocated_amount_rupiah:int,
     *   allocation_priority:int
     * }>
     */
    private function allocateAcrossComponents(string $paymentId, string $noteId, int $amount): array
    {
        $components = $this->loadPayableComponents($noteId);

        $existing = DB::table('payment_component_allocations')
            ->select('component_type', 'component_ref_id', DB::raw('SUM(allocated_amount_rupiah) as total'))
            ->where('note_id', $noteId)
            ->groupBy('component_type', 'component_ref_id')
            ->get();

        $allocatedByComponent = [];
        foreach ($existing as $row) {
            $key = $this->componentKey((string) $row->component_type, (string) $row->component_ref_id);
            $allocatedByComponent[$key] = (int) $row->total;
        }

        $remaining = $amount;
        $priority = 1;
        $allocations = [];

        foreach ($components as $component) {
            $key = $this->componentKey($component['component_type'], $component['component_ref_id']);
            $already = $allocatedByComponent[$key] ?? 0;
            $available = max($component['component_amount_rupiah_snapshot'] - $already, 0);

            if ($available === 0) {
                continue;
            }

            $take = min($remaining, $available);

            if ($take <= 0) {
                break;
            }

            $allocations[] = [
                'id' => sprintf('seed-pay-comp-load-%s-%02d', str_replace('seed-pay-load-', '', $paymentId), $priority),
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => $component['work_item_id'],
                'component_type' => $component['component_type'],
                'component_ref_id' => $component['component_ref_id'],
                'component_amount_rupiah_snapshot' => $component['component_amount_rupiah_snapshot'],
                'allocated_amount_rupiah' => $take,
                'allocation_priority' => $priority,
            ];

            $allocatedByComponent[$key] = $already + $take;
            $remaining -= $take;
            $priority++;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException(sprintf(
                'Payment load [%s] untuk note [%s] tidak bisa dialokasikan penuh ke komponen note.',
                $paymentId,
                $noteId
            ));
        }

        return $allocations;
    }

    /**
     * @return list<array{
     *   work_item_id:string,
     *   component_type:string,
     *   component_ref_id:string,
     *   component_amount_rupiah_snapshot:int,
     *   order_no:int
     * }>
     */
    private function loadPayableComponents(string $noteId): array
    {
        $workItems = DB::table('work_items')
            ->select('id', 'transaction_type', 'subtotal_rupiah', 'line_no')
            ->where('note_id', $noteId)
            ->orderBy('line_no')
            ->get();

        $components = [];
        $orderNo = 1;

        foreach ($workItems as $item) {
            $workItemId = (string) $item->id;
            $type = (string) $item->transaction_type;

            if ($type === 'store_stock_sale_only') {
                $components[] = [
                    'work_item_id' => $workItemId,
                    'component_type' => PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
                    'component_ref_id' => $workItemId,
                    'component_amount_rupiah_snapshot' => (int) $item->subtotal_rupiah,
                    'order_no' => $orderNo++,
                ];
                continue;
            }

            if ($type === 'service_with_store_stock_part') {
                $stockLines = DB::table('work_item_store_stock_lines')
                    ->select('id', 'line_total_rupiah')
                    ->where('work_item_id', $workItemId)
                    ->orderBy('id')
                    ->get();

                foreach ($stockLines as $line) {
                    $components[] = [
                        'work_item_id' => $workItemId,
                        'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                        'component_ref_id' => (string) $line->id,
                        'component_amount_rupiah_snapshot' => (int) $line->line_total_rupiah,
                        'order_no' => $orderNo++,
                    ];
                }

                $serviceDetail = DB::table('work_item_service_details')
                    ->select('service_price_rupiah')
                    ->where('work_item_id', $workItemId)
                    ->first();

                if ($serviceDetail === null) {
                    throw new \RuntimeException(sprintf('Service detail tidak ditemukan untuk work item [%s].', $workItemId));
                }

                $components[] = [
                    'work_item_id' => $workItemId,
                    'component_type' => PaymentComponentType::SERVICE_FEE,
                    'component_ref_id' => $workItemId,
                    'component_amount_rupiah_snapshot' => (int) $serviceDetail->service_price_rupiah,
                    'order_no' => $orderNo++,
                ];

                continue;
            }

            if ($type === 'service_with_external_purchase') {
                $externalLines = DB::table('work_item_external_purchase_lines')
                    ->select('id', 'unit_cost_rupiah', 'qty')
                    ->where('work_item_id', $workItemId)
                    ->orderBy('id')
                    ->get();

                foreach ($externalLines as $line) {
                    $components[] = [
                        'work_item_id' => $workItemId,
                        'component_type' => PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART,
                        'component_ref_id' => (string) $line->id,
                        'component_amount_rupiah_snapshot' => ((int) $line->unit_cost_rupiah) * ((int) $line->qty),
                        'order_no' => $orderNo++,
                    ];
                }

                $serviceDetail = DB::table('work_item_service_details')
                    ->select('service_price_rupiah')
                    ->where('work_item_id', $workItemId)
                    ->first();

                if ($serviceDetail === null) {
                    throw new \RuntimeException(sprintf('Service detail tidak ditemukan untuk work item [%s].', $workItemId));
                }

                $components[] = [
                    'work_item_id' => $workItemId,
                    'component_type' => PaymentComponentType::SERVICE_FEE,
                    'component_ref_id' => $workItemId,
                    'component_amount_rupiah_snapshot' => (int) $serviceDetail->service_price_rupiah,
                    'order_no' => $orderNo++,
                ];

                continue;
            }

            if ($type === 'service_only') {
                $serviceDetail = DB::table('work_item_service_details')
                    ->select('service_price_rupiah')
                    ->where('work_item_id', $workItemId)
                    ->first();

                if ($serviceDetail === null) {
                    throw new \RuntimeException(sprintf('Service detail tidak ditemukan untuk work item [%s].', $workItemId));
                }

                $components[] = [
                    'work_item_id' => $workItemId,
                    'component_type' => PaymentComponentType::SERVICE_FEE,
                    'component_ref_id' => $workItemId,
                    'component_amount_rupiah_snapshot' => (int) $serviceDetail->service_price_rupiah,
                    'order_no' => $orderNo++,
                ];

                continue;
            }

            throw new \RuntimeException(sprintf(
                'Transaction type [%s] belum didukung untuk payable component load note [%s].',
                $type,
                $noteId
            ));
        }

        usort(
            $components,
            static fn (array $a, array $b): int => $a['order_no'] <=> $b['order_no']
        );

        return $components;
    }

    private function paymentId(string $noteId, int $sequence): string
    {
        return sprintf('seed-pay-load-%s-%02d', str_replace('seed-note-load-', '', $noteId), $sequence);
    }

    private function allocationId(string $noteId, int $sequence): string
    {
        return sprintf('seed-pay-alloc-load-%s-%02d', str_replace('seed-note-load-', '', $noteId), $sequence);
    }

    private function componentKey(string $componentType, string $componentRefId): string
    {
        return $componentType.'::'.$componentRefId;
    }
}
