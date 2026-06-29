<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement\Concerns;

trait BuildsProcurementInvoiceDetailVersionTimelineView
{
    use BuildsProcurementInvoiceDetailVersionChangeSummaryView;

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

        $revisionNo = (int) ($version['revision_no'] ?? 0);

        return [
            'detail_id' => 'supplier-invoice-version-detail-' . $revisionNo,
            'revision_label' => 'Revisi ' . $revisionNo,
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
            'ukuran' => ($line['product_ukuran_snapshot'] ?? null) !== null ? (string) $line['product_ukuran_snapshot'] : '-',
            'qty_pcs' => (int) ($line['qty_pcs'] ?? 0),
            'line_subtotal_before_tax_label' => $this->formatRupiah((int) ($line['line_subtotal_before_tax_rupiah'] ?? $line['line_total_rupiah'] ?? 0)),
            'tax_amount_label' => $this->formatRupiah((int) ($line['tax_amount_rupiah'] ?? 0)),
            'tax_input' => $taxInput !== null ? (string) $taxInput : null,
            'line_total_label' => $this->formatRupiah((int) ($line['line_total_rupiah'] ?? 0)),
        ];
    }
}
