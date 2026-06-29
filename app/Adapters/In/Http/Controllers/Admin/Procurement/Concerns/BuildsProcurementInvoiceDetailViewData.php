<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailViewData
{
    use BuildsProcurementInvoiceDetailLinesView;
    use BuildsProcurementInvoiceDetailPolicyView;
    use BuildsProcurementInvoiceDetailSummaryView;
    use FormatsProcurementInvoiceDetailViewValue;

    /**
     * @param array<string, mixed> $detail
     * @return array<string, mixed>
     */
    private function buildViewData(array $detail): array
    {
        $summary = is_array($detail['summary'] ?? null) ? $detail['summary'] : [];
        $lines = is_array($detail['lines'] ?? null) ? $detail['lines'] : [];
        $versionTimeline = is_array($detail['version_timeline'] ?? null) ? $detail['version_timeline'] : [];

        return [
            'summaryView' => $this->buildSummaryView($summary),
            'linesView' => $this->buildLinesView($lines),
            'policyView' => $this->buildPolicyView($summary),
            'versionTimelineView' => $this->buildVersionTimelineView($versionTimeline),
        ];
    }

    /**
     * @param list<array<string, mixed>> $versions
     * @return list<array<string, mixed>>
     */
    private function buildVersionTimelineView(array $versions): array
    {
        $versionsByRevisionNo = [];

        foreach ($versions as $version) {
            if (! is_array($version)) {
                continue;
            }

            $versionsByRevisionNo[(int) ($version['revision_no'] ?? 0)] = $version;
        }

        return array_map(function (array $version) use ($versionsByRevisionNo): array {
            $revisionNo = (int) ($version['revision_no'] ?? 0);
            $previousVersion = $versionsByRevisionNo[$revisionNo - 1] ?? null;
            $previousSnapshot = is_array($previousVersion)
                && is_array($previousVersion['snapshot'] ?? null)
                    ? $previousVersion['snapshot']
                    : null;

            return $this->buildVersionTimelineEntryView($version, $previousSnapshot);
        }, $versions);
    }

    /**
     * @param array<string, mixed> $version
     * @return array<string, mixed>
     */
    private function buildVersionTimelineEntryView(array $version, ?array $previousSnapshot = null): array
    {
        $snapshot = is_array($version['snapshot'] ?? null) ? $version['snapshot'] : [];
        $supplier = is_array($snapshot['supplier'] ?? null) ? $snapshot['supplier'] : [];
        $lines = is_array($snapshot['lines'] ?? null) ? $snapshot['lines'] : [];
        $taxInput = $snapshot['tax_input'] ?? null;

        return [
            'revision_label' => 'Revisi ' . (int) ($version['revision_no'] ?? 0),
            'event_name' => (string) ($version['event_name'] ?? ''),
            'changed_at' => (string) ($version['changed_at'] ?? ''),
            'actor_label' => null,
            'reason_label' => ($version['change_reason'] ?? null) !== null
                ? (string) $version['change_reason']
                : null,
            'change_summary' => $this->buildVersionTimelineChangeSummary($previousSnapshot, $snapshot),
            'snapshot' => [
                'nomor_faktur' => (string) (($snapshot['nomor_faktur'] ?? null) ?: '-'),
                'supplier_name' => (string) (($supplier['nama_pt_pengirim_snapshot'] ?? null) ?: '-'),
                'shipment_date' => (string) (($snapshot['tanggal_pengiriman'] ?? null) ?: '-'),
                'due_date' => (string) (($snapshot['jatuh_tempo'] ?? null) ?: '-'),
                'subtotal_before_tax_label' => $this->formatRupiah((int) ($snapshot['subtotal_before_tax_rupiah'] ?? $snapshot['grand_total_rupiah'] ?? 0)),
                'tax_amount_label' => $this->formatRupiah((int) ($snapshot['tax_amount_rupiah'] ?? 0)),
                'tax_input' => $taxInput !== null ? (string) $taxInput : null,
                'grand_total_label' => $this->formatRupiah((int) ($snapshot['grand_total_rupiah'] ?? 0)),
                'lines' => array_map(fn (array $line): array => $this->buildVersionTimelineLineView($line), $lines),
            ],
        ];
    }

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

    /**
     * @param list<string> $changes
     * @param array<string, mixed> $previousSnapshot
     * @param array<string, mixed> $snapshot
     */
    private function appendLineChanges(array &$changes, array $previousSnapshot, array $snapshot): void
    {
        $previousLines = $this->versionLinesByComparisonKey(is_array($previousSnapshot['lines'] ?? null) ? $previousSnapshot['lines'] : []);
        $currentLines = $this->versionLinesByComparisonKey(is_array($snapshot['lines'] ?? null) ? $snapshot['lines'] : []);

        foreach ($currentLines as $key => $currentLine) {
            if (! isset($previousLines[$key])) {
                $changes[] = sprintf(
                    'Tambah Rincian: %s, Qty %d, Total %s',
                    $this->versionLineName($currentLine),
                    (int) ($currentLine['qty_pcs'] ?? 0),
                    $this->formatRupiah((int) ($currentLine['line_total_rupiah'] ?? 0)),
                );

                continue;
            }

            $previousLine = $previousLines[$key];
            $previousQty = (int) ($previousLine['qty_pcs'] ?? 0);
            $currentQty = (int) ($currentLine['qty_pcs'] ?? 0);

            if ($previousQty !== $currentQty) {
                $changes[] = sprintf(
                    '%s: Qty %d → %d',
                    $this->versionLineName($currentLine),
                    $previousQty,
                    $currentQty,
                );
            }
        }

        foreach ($previousLines as $key => $previousLine) {
            if (isset($currentLines[$key])) {
                continue;
            }

            $changes[] = sprintf(
                'Hapus Rincian: %s, Qty %d, Total %s',
                $this->versionLineName($previousLine),
                (int) ($previousLine['qty_pcs'] ?? 0),
                $this->formatRupiah((int) ($previousLine['line_total_rupiah'] ?? 0)),
            );
        }
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return array<string, array<string, mixed>>
     */
    private function versionLinesByComparisonKey(array $lines): array
    {
        $mapped = [];

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $kodeBarang = trim((string) ($line['product_kode_barang_snapshot'] ?? ''));
            $namaBarang = trim((string) ($line['product_nama_barang_snapshot'] ?? ''));
            $merek = trim((string) ($line['product_merek_snapshot'] ?? ''));
            $ukuran = trim((string) ($line['product_ukuran_snapshot'] ?? ''));

            $key = $kodeBarang !== ''
                ? 'kode:' . $kodeBarang
                : sprintf('line:%s:%s:%s:%d', $namaBarang, $merek, $ukuran, $index);

            $mapped[$key] = $line;
        }

        return $mapped;
    }

    /**
     * @param array<string, mixed> $line
     */
    private function versionLineName(array $line): string
    {
        $name = trim((string) ($line['product_nama_barang_snapshot'] ?? ''));

        return $name !== '' ? $name : 'Rincian tanpa nama';
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function buildVersionTimelineLineView(array $line): array
    {
        $taxInput = $line['tax_input'] ?? null;

        return [
            'line_no' => (int) ($line['line_no'] ?? 0),
            'kode_barang' => (string) (($line['product_kode_barang_snapshot'] ?? null) ?: '-'),
            'nama_barang' => (string) (($line['product_nama_barang_snapshot'] ?? null) ?: '-'),
            'merek' => (string) (($line['product_merek_snapshot'] ?? null) ?: '-'),
            'ukuran' => ($line['product_ukuran_snapshot'] ?? null) !== null
                ? (string) $line['product_ukuran_snapshot']
                : '-',
            'qty_pcs' => (int) ($line['qty_pcs'] ?? 0),
            'line_subtotal_before_tax_label' => $this->formatRupiah((int) ($line['line_subtotal_before_tax_rupiah'] ?? $line['line_total_rupiah'] ?? 0)),
            'tax_amount_label' => $this->formatRupiah((int) ($line['tax_amount_rupiah'] ?? 0)),
            'tax_input' => $taxInput !== null ? (string) $taxInput : null,
            'line_total_label' => $this->formatRupiah((int) ($line['line_total_rupiah'] ?? 0)),
        ];
    }
}
