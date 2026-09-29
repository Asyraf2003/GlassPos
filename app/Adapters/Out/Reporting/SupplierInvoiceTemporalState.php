<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

final class SupplierInvoiceTemporalState
{
    public function at(object $invoice, array $versions, string $cutoff): ?array
    {
        if ($versions === []) {
            if ((int) $invoice->last_revision_no > 0) {
                throw new \RuntimeException('Supplier invoice history unavailable: '.$invoice->id);
            }
            $state = [
                'nomor_faktur' => $invoice->nomor_faktur,
                'supplier' => ['id' => $invoice->supplier_id, 'nama_pt_pengirim_snapshot' => $invoice->supplier_nama_pt_pengirim_snapshot],
                'tanggal_pengiriman' => $invoice->tanggal_pengiriman,
                'jatuh_tempo' => $invoice->jatuh_tempo,
                'grand_total_rupiah' => (int) $invoice->grand_total_rupiah,
            ];
            if ($invoice->tanggal_pengiriman > substr($cutoff, 0, 10)) {
                return null;
            }
        } else {
            if ($versions[0]->event_name !== 'supplier_invoice_created' && $versions[0]->changed_at > $cutoff) {
                throw new \RuntimeException('Supplier invoice initial history unavailable: '.$invoice->id);
            }
            $state = null;
            foreach ($versions as $version) {
                if ($version->changed_at <= $cutoff) {
                    $state = json_decode($version->snapshot_json, true, flags: JSON_THROW_ON_ERROR);
                }
            }
            if ($state === null) {
                return null;
            }
        }
        if ($invoice->voided_at !== null && $invoice->voided_at <= $cutoff) {
            $state['grand_total_rupiah'] = 0;
            $state['voided'] = true;
        }

        return $state;
    }
}
