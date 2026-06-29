<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailVersionLineChangeSummaryView
{
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
                $changes[] = sprintf('%s: Qty %d → %d', $this->versionLineName($currentLine), $previousQty, $currentQty);
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
            $key = $kodeBarang !== '' ? 'kode:' . $kodeBarang : sprintf('line:%s:%s:%s:%d', $namaBarang, $merek, $ukuran, $index);

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
}
