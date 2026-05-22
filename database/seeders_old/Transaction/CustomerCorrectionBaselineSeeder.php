<?php

declare(strict_types=1);

namespace Database\Seeders\Transaction;

use App\Application\Note\UseCases\CorrectPaidServiceOnlyWorkItemHandler;
use App\Application\Note\UseCases\CorrectPaidWorkItemStatusHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CustomerCorrectionBaselineSeeder extends Seeder
{
    public function run(
        CorrectPaidWorkItemStatusHandler $correctStatus,
        CorrectPaidServiceOnlyWorkItemHandler $correctServiceOnly,
    ): void {
        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');

        $nominalTarget = $this->findNominalTarget();
        $statusTarget = $this->findStatusTarget($nominalTarget?->note_id !== null ? (string) $nominalTarget->note_id : null);

        if ($nominalTarget === null && $statusTarget === null) {
            $this->command?->warn('CustomerCorrectionBaselineSeeder dilewati: target correction baseline tidak ditemukan.');
            return;
        }

        if ($statusTarget !== null) {
            $this->resetStatusTarget((string) $statusTarget->note_id, (string) $statusTarget->work_item_id);

            $result = $correctStatus->handle(
                (string) $statusTarget->note_id,
                1,
                'done',
                'Seed baseline correction status paid note',
                $adminId,
            );

            if ($result->isFailure()) {
                $errors = json_encode($result->errors(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                throw new \RuntimeException(
                    sprintf(
                        'Gagal status correction baseline untuk note [%s]: %s',
                        (string) $statusTarget->note_id,
                        $errors
                    )
                );
            }
        }

        if ($nominalTarget !== null) {
            [$baselineServiceName, $baselineServicePrice] = $this->baselineServiceOnlyProfile(
                CarbonImmutable::parse((string) $nominalTarget->transaction_date)
            );

            $this->resetNominalTarget(
                noteId: (string) $nominalTarget->note_id,
                workItemId: (string) $nominalTarget->work_item_id,
                serviceName: $baselineServiceName,
                servicePrice: $baselineServicePrice,
            );

            $correctedPrice = max(1000, $baselineServicePrice - 5000);

            $result = $correctServiceOnly->handle(
                (string) $nominalTarget->note_id,
                1,
                $baselineServiceName . ' Koreksi',
                $correctedPrice,
                ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                'Seed baseline correction nominal service_only',
                $adminId,
            );

            if ($result->isFailure()) {
                $errors = json_encode($result->errors(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                throw new \RuntimeException(
                    sprintf(
                        'Gagal nominal correction baseline untuk note [%s]: %s',
                        (string) $nominalTarget->note_id,
                        $errors
                    )
                );
            }
        }

        $this->command?->info('CustomerCorrectionBaselineSeeder selesai: baseline correction 1 bulan dibuat.');
    }

    private function findNominalTarget(): ?object
    {
        return DB::table('notes as n')
            ->join('work_items as w', function ($join): void {
                $join->on('w.note_id', '=', 'n.id')
                    ->where('w.line_no', '=', 1)
                    ->where('w.transaction_type', '=', 'service_only');
            })
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_allocated from payment_allocations group by note_id) as pa'),
                'pa.note_id',
                '=',
                'n.id'
            )
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_refunded from customer_refunds group by note_id) as cr'),
                'cr.note_id',
                '=',
                'n.id'
            )
            ->select('n.id as note_id', 'n.transaction_date', 'w.id as work_item_id')
            ->where('n.id', 'like', 'seed-note-bl-%-01')
            ->whereRaw('COALESCE(pa.total_allocated, 0) >= n.total_rupiah')
            ->whereRaw('COALESCE(cr.total_refunded, 0) = 0')
            ->orderBy('n.transaction_date')
            ->orderBy('n.id')
            ->first();
    }

    private function findStatusTarget(?string $excludeNoteId): ?object
    {
        $query = DB::table('notes as n')
            ->join('work_items as w', function ($join): void {
                $join->on('w.note_id', '=', 'n.id')
                    ->where('w.line_no', '=', 1);
            })
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_allocated from payment_allocations group by note_id) as pa'),
                'pa.note_id',
                '=',
                'n.id'
            )
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_refunded from customer_refunds group by note_id) as cr'),
                'cr.note_id',
                '=',
                'n.id'
            )
            ->select('n.id as note_id', 'w.id as work_item_id')
            ->where('n.id', 'like', 'seed-note-bl-%-02')
            ->whereRaw('COALESCE(pa.total_allocated, 0) >= n.total_rupiah')
            ->whereRaw('COALESCE(cr.total_refunded, 0) = 0');

        if ($excludeNoteId !== null) {
            $query->where('n.id', '!=', $excludeNoteId);
        }

        $target = $query
            ->orderBy('n.transaction_date')
            ->orderBy('n.id')
            ->first();

        if ($target !== null) {
            return $target;
        }

        $fallback = DB::table('notes as n')
            ->join('work_items as w', function ($join): void {
                $join->on('w.note_id', '=', 'n.id')
                    ->where('w.line_no', '=', 1);
            })
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_allocated from payment_allocations group by note_id) as pa'),
                'pa.note_id',
                '=',
                'n.id'
            )
            ->leftJoin(
                DB::raw('(select note_id, sum(amount_rupiah) as total_refunded from customer_refunds group by note_id) as cr'),
                'cr.note_id',
                '=',
                'n.id'
            )
            ->select('n.id as note_id', 'w.id as work_item_id')
            ->where('n.id', 'like', 'seed-note-bl-%')
            ->whereRaw('COALESCE(pa.total_allocated, 0) >= n.total_rupiah')
            ->whereRaw('COALESCE(cr.total_refunded, 0) = 0');

        if ($excludeNoteId !== null) {
            $fallback->where('n.id', '!=', $excludeNoteId);
        }

        return $fallback
            ->orderBy('n.transaction_date')
            ->orderBy('n.id')
            ->first();
    }

    private function resetStatusTarget(string $noteId, string $workItemId): void
    {
        DB::table('work_items')
            ->where('id', $workItemId)
            ->update(['status' => 'open']);

        DB::table('audit_logs')
            ->where('event', 'paid_work_item_status_corrected')
            ->where('context', 'like', '%"note_id":"'.$noteId.'"%')
            ->delete();
    }

    private function resetNominalTarget(
        string $noteId,
        string $workItemId,
        string $serviceName,
        int $servicePrice,
    ): void {
        $eventIds = DB::table('note_mutation_events')
            ->where('note_id', $noteId)
            ->where('mutation_type', 'paid_service_only_work_item_corrected')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        if ($eventIds !== []) {
            DB::table('note_mutation_snapshots')
                ->whereIn('note_mutation_event_id', $eventIds)
                ->delete();

            DB::table('note_mutation_events')
                ->whereIn('id', $eventIds)
                ->delete();
        }

        DB::table('audit_logs')
            ->where('event', 'paid_service_only_work_item_corrected')
            ->where('context', 'like', '%"note_id":"'.$noteId.'"%')
            ->delete();

        DB::table('work_item_service_details')
            ->where('work_item_id', $workItemId)
            ->update([
                'service_name' => $serviceName,
                'service_price_rupiah' => $servicePrice,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ]);

        DB::table('work_items')
            ->where('id', $workItemId)
            ->update([
                'status' => 'open',
                'subtotal_rupiah' => $servicePrice,
            ]);

        DB::table('notes')
            ->where('id', $noteId)
            ->update([
                'total_rupiah' => $servicePrice,
            ]);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function baselineServiceOnlyProfile(CarbonImmutable $transactionDate): array
    {
        $startDate = CarbonImmutable::parse(
            (string) DB::table('notes')
                ->where('id', 'like', 'seed-note-bl-%')
                ->min('transaction_date')
        );

        $dayIndex = (int) $startDate->diffInDays($transactionDate);

        return [
            sprintf('Servis Ringan D%02d S01', $dayIndex + 1),
            50000 + ($dayIndex * 2500),
        ];
    }
}
