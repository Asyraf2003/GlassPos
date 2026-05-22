<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

use App\Application\Note\UseCases\CorrectPaidServiceOnlyWorkItemHandler;
use App\Application\Note\UseCases\CorrectPaidWorkItemStatusHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedDensity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerCorrectionLoadSeeder extends Seeder
{
    private ?CarbonImmutable $loadStartDate = null;

    public function run(
        CorrectPaidWorkItemStatusHandler $correctStatus,
        CorrectPaidServiceOnlyWorkItemHandler $correctServiceOnly,
    ): void {
        $density = SeedDensity::monster();
        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');
        $this->loadStartDate = CarbonImmutable::parse(
            (string) DB::table('notes')->where('id', 'like', 'seed-note-load-%')->min('transaction_date')
        );

        $paidNotes = $this->paidLoadNotes();

        if ($paidNotes->isEmpty()) {
            $this->command?->warn('CustomerCorrectionLoadSeeder dilewati: paid note monster tidak ditemukan.');
            return;
        }

        $targetCount = max(1, (int) ceil($paidNotes->count() * (((int) $density['correction_paid_note_percent']) / 100)));

        if ($targetCount === 1) {
            $nominalTargetCount = 1;
            $statusTargetCount = 0;
        } else {
            $nominalTargetCount = max(1, (int) floor($targetCount / 2));
            $statusTargetCount = max(1, $targetCount - $nominalTargetCount);
        }

        $nominalTargets = $this->pickNominalTargets($nominalTargetCount);
        $statusTargets = $this->pickStatusTargets($statusTargetCount, $nominalTargets);

        if ($nominalTargets->isEmpty() && $statusTargets->isEmpty()) {
            $this->command?->warn('CustomerCorrectionLoadSeeder dilewati: target correction monster tidak ditemukan.');
            return;
        }

        foreach ($statusTargets->values() as $index => $target) {
            $noteId = (string) $target->note_id;
            $workItemId = (string) $target->work_item_id;

            $this->resetStatusTarget($noteId, $workItemId);

            $result = $correctStatus->handle(
                $noteId,
                1,
                'done',
                sprintf('Seed monster correction status #%03d', $index + 1),
                $adminId,
            );

            if ($result->isFailure()) {
                $errors = json_encode($result->errors(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                throw new \RuntimeException(sprintf(
                    'Gagal status correction load untuk note [%s]: %s',
                    $noteId,
                    $errors
                ));
            }
        }

        foreach ($nominalTargets->values() as $index => $target) {
            $noteId = (string) $target->note_id;
            $workItemId = (string) $target->work_item_id;

            [$baselineServiceName, $baselineServicePrice] = $this->baselineServiceOnlyProfile(
                $noteId,
                CarbonImmutable::parse((string) $target->transaction_date)
            );

            $this->resetNominalTarget(
                noteId: $noteId,
                workItemId: $workItemId,
                serviceName: $baselineServiceName,
                servicePrice: $baselineServicePrice,
            );

            $delta = 3000 + (($index % 5) * 1000);
            $correctedPrice = max(1000, $baselineServicePrice - $delta);

            $result = $correctServiceOnly->handle(
                $noteId,
                1,
                $baselineServiceName . ' Koreksi',
                $correctedPrice,
                ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                sprintf('Seed monster correction nominal #%03d', $index + 1),
                $adminId,
            );

            if ($result->isFailure()) {
                $errors = json_encode($result->errors(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                throw new \RuntimeException(sprintf(
                    'Gagal nominal correction load untuk note [%s]: %s',
                    $noteId,
                    $errors
                ));
            }
        }

        $this->command?->info('CustomerCorrectionLoadSeeder selesai: correction monster 1 tahun dibuat.');
    }

    /**
     * @return Collection<int, object>
     */
    private function paidLoadNotes(): Collection
    {
        return DB::table('notes as n')
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
            ->select(
                'n.id as note_id',
                'n.transaction_date',
                'w.id as work_item_id',
                'w.transaction_type'
            )
            ->where('n.id', 'like', 'seed-note-load-%')
            ->whereRaw('COALESCE(pa.total_allocated, 0) >= n.total_rupiah')
            ->whereRaw('COALESCE(cr.total_refunded, 0) = 0')
            ->orderBy('n.transaction_date')
            ->orderBy('n.id')
            ->get()
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    private function pickNominalTargets(int $count): Collection
    {
        $rows = $this->paidLoadNotes()
            ->filter(static fn (object $row): bool => (string) $row->transaction_type === 'service_only')
            ->values();

        return $this->pickSpacedTargets($rows, $count);
    }

    /**
     * @param Collection<int, object> $nominalTargets
     * @return Collection<int, object>
     */
    private function pickStatusTargets(int $count, Collection $nominalTargets): Collection
    {
        $excludedNoteIds = $nominalTargets
            ->map(static fn (object $row): string => (string) $row->note_id)
            ->all();

        $rows = $this->paidLoadNotes()
            ->reject(static fn (object $row): bool => in_array((string) $row->note_id, $excludedNoteIds, true))
            ->values();

        return $this->pickSpacedTargets($rows, $count);
    }

    /**
     * @param Collection<int, object> $rows
     * @return Collection<int, object>
     */
    private function pickSpacedTargets(Collection $rows, int $count): Collection
    {
        if ($count <= 0 || $rows->isEmpty()) {
            return collect();
        }

        $step = max(1, (int) floor($rows->count() / $count));
        $targets = collect();

        foreach ($rows->values() as $index => $row) {
            if (($index % $step) === 0) {
                $targets->push($row);
            }

            if ($targets->count() >= $count) {
                break;
            }
        }

        return $targets->take($count)->values();
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

        $newTotal = (int) DB::table('work_items')
            ->where('note_id', $noteId)
            ->sum('subtotal_rupiah');

        DB::table('notes')
            ->where('id', $noteId)
            ->update([
                'total_rupiah' => $newTotal,
            ]);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function baselineServiceOnlyProfile(string $noteId, CarbonImmutable $transactionDate): array
    {
        if ($this->loadStartDate === null) {
            throw new \RuntimeException('Load start date belum tersedia untuk baseline service_only profile.');
        }

        $slot = $this->extractSlotFromNoteId($noteId);
        $dayIndex = (int) $this->loadStartDate->diffInDays($transactionDate);
        $scenario = ($dayIndex + $slot) % 8;

        [$label, $basePrice] = match ($scenario) {
            0 => ['Servis Ringan', 55000],
            3 => ['Servis Paket', 60000],
            5 => ['Tune Up', 80000],
            7 => ['Servis Awal', 50000],
            default => throw new \RuntimeException(sprintf(
                'Note [%s] bukan target service_only line-1 yang valid untuk nominal correction.',
                $noteId
            )),
        };

        $resolvedPrice = (int) ($basePrice + (($dayIndex % 9) * 1500) + (($slot % 5) * 1000));

        return [
            sprintf('%s D%03d S%02d', $label, $dayIndex + 1, $slot),
            $resolvedPrice,
        ];
    }

    private function extractSlotFromNoteId(string $noteId): int
    {
        $parts = explode('-', $noteId);
        $slot = end($parts);

        if (!is_string($slot) || !ctype_digit($slot)) {
            throw new \RuntimeException(sprintf('Slot tidak bisa diekstrak dari note id [%s].', $noteId));
        }

        return (int) $slot;
    }
}
