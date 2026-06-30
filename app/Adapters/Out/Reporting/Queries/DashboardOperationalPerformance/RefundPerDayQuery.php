<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardOperationalPerformance;

use Illuminate\Support\Facades\DB;

final class RefundPerDayQuery
{
    /**
     * @return list<array{
     *   period_key:string,
     *   period_label:string,
     *   amount_rupiah:int
     * }>
     */
    public function rows(string $fromDate, string $toDate): array
    {
        $rowsByDate = [];

        foreach ($this->customerRefundRows($fromDate, $toDate) as $row) {
            $periodKey = $row['period_key'];
            $rowsByDate[$periodKey] = ($rowsByDate[$periodKey] ?? 0) + $row['amount_rupiah'];
        }

        foreach ($this->surplusRefundPaidRows($fromDate, $toDate) as $row) {
            $periodKey = $row['period_key'];
            $rowsByDate[$periodKey] = ($rowsByDate[$periodKey] ?? 0) + $row['amount_rupiah'];
        }

        ksort($rowsByDate);

        return array_map(
            static fn (string $periodKey, int $amountRupiah): array => [
                'period_key' => $periodKey,
                'period_label' => $periodKey,
                'amount_rupiah' => $amountRupiah,
            ],
            array_keys($rowsByDate),
            array_values($rowsByDate),
        );
    }

    /**
     * @return list<array{period_key:string, amount_rupiah:int}>
     */
    private function customerRefundRows(string $fromDate, string $toDate): array
    {
        return DB::table('customer_refunds')
            ->whereBetween('refunded_at', [
                $this->startOfDay($fromDate),
                $this->endOfDay($toDate),
            ])
            ->selectRaw('DATE(refunded_at) as period_key, COALESCE(SUM(amount_rupiah), 0) as amount_rupiah')
            ->groupBy(DB::raw('DATE(refunded_at)'))
            ->orderBy(DB::raw('DATE(refunded_at)'))
            ->get()
            ->map(static fn (object $row): array => [
                'period_key' => (string) $row->period_key,
                'amount_rupiah' => (int) $row->amount_rupiah,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{period_key:string, amount_rupiah:int}>
     */
    private function surplusRefundPaidRows(string $fromDate, string $toDate): array
    {
        return DB::table('note_revision_surplus_refund_payments')
            ->where('status', 'active')
            ->whereBetween('effective_date', [$fromDate, $toDate])
            ->selectRaw('effective_date as period_key, COALESCE(SUM(amount_rupiah), 0) as amount_rupiah')
            ->groupBy('effective_date')
            ->orderBy('effective_date')
            ->get()
            ->map(static fn (object $row): array => [
                'period_key' => (string) $row->period_key,
                'amount_rupiah' => (int) $row->amount_rupiah,
            ])
            ->values()
            ->all();
    }

    private function startOfDay(string $date): string
    {
        return $date . ' 00:00:00';
    }

    private function endOfDay(string $date): string
    {
        return $date . ' 23:59:59';
    }
}
