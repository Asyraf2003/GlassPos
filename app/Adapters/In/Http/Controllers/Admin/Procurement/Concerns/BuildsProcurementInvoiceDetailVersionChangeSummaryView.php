<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailVersionChangeSummaryView
{
    use BuildsProcurementInvoiceDetailVersionLineChangeSummaryView;

    /**
     * @param array<string, mixed>|null $previousSnapshot
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function buildVersionTimelineChangeSummary(?array $previousSnapshot, array $snapshot): array
    {
        if ($previousSnapshot === null || $previousSnapshot === []) {
            return [];
        }

        $changes = [];

        $this->appendChangedScalar(
            $changes,
            'Nomor Faktur',
            $previousSnapshot['nomor_faktur'] ?? null,
            $snapshot['nomor_faktur'] ?? null,
        );

        $this->appendChangedScalar(
            $changes,
            'Tanggal Pengiriman',
            $previousSnapshot['tanggal_pengiriman'] ?? null,
            $snapshot['tanggal_pengiriman'] ?? null,
        );

        $this->appendChangedMoney(
            $changes,
            'Total Nota',
            $previousSnapshot['grand_total_rupiah'] ?? null,
            $snapshot['grand_total_rupiah'] ?? null,
        );

        $this->appendLineChanges($changes, $previousSnapshot, $snapshot);

        return $changes;
    }

    /**
     * @param list<string> $changes
     */
    private function appendChangedScalar(array &$changes, string $label, mixed $previous, mixed $current): void
    {
        $previousValue = trim((string) ($previous ?? ''));
        $currentValue = trim((string) ($current ?? ''));

        if ($previousValue === $currentValue) {
            return;
        }

        $changes[] = sprintf('%s: %s → %s', $label, $previousValue !== '' ? $previousValue : '-', $currentValue !== '' ? $currentValue : '-');
    }

    /**
     * @param list<string> $changes
     */
    private function appendChangedMoney(array &$changes, string $label, mixed $previous, mixed $current): void
    {
        $previousValue = (int) ($previous ?? 0);
        $currentValue = (int) ($current ?? 0);

        if ($previousValue === $currentValue) {
            return;
        }

        $changes[] = sprintf('%s: %s → %s', $label, $this->formatRupiah($previousValue), $this->formatRupiah($currentValue));
    }
}
