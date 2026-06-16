<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Note\WorkItem\WorkItem;

final class NoteRevisionLineSnapshotViewMapper
{
    public function __construct(
        private readonly NoteRevisionLineSnapshotLabelResolver $labels,
    ) {
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @return list<array<string, mixed>>
     */
    public function mapMany(array $lines): array
    {
        return array_map(fn (NoteRevisionLineSnapshot $line): array => $this->map($line), $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function map(NoteRevisionLineSnapshot $line): array
    {
        return [
            'line_no' => $line->lineNo(),
            'label' => $this->labels->resolve($line),
            'type_label' => $this->typeLabel($line->transactionType()),
            'status' => $line->status(),
            'subtotal_rupiah' => $line->subtotalRupiah(),
            'service_price_rupiah' => $line->servicePriceRupiah(),
            'details' => $this->details($line),
        ];
    }

    /**
     * @return list<string>
     */
    private function details(NoteRevisionLineSnapshot $line): array
    {
        $details = [];
        $payload = $line->payload();

        if ($line->serviceLabel() !== null) {
            $details[] = sprintf(
                'Servis: %s%s',
                $line->serviceLabel(),
                $line->servicePriceRupiah() !== null
                    ? ' · ' . $this->money($line->servicePriceRupiah())
                    : ''
            );
        }

        $storeLines = is_array($payload['store_stock_lines'] ?? null)
            ? array_values(array_filter($payload['store_stock_lines'], 'is_array'))
            : [];

        foreach ($storeLines as $storeLine) {
            $taxAmount = (int) ($storeLine['tax_amount_rupiah'] ?? 0);

            if ($taxAmount <= 0) {
                continue;
            }

            $productLabel = trim((string) (
                $storeLine['product_name_snapshot']
                ?? $storeLine['product_name']
                ?? $storeLine['product_id']
                ?? 'Produk'
            ));

            $taxInput = trim((string) ($storeLine['tax_input'] ?? ''));

            $details[] = sprintf(
                'Pajak Produk: %s%s · %s',
                $productLabel !== '' ? $productLabel : 'Produk',
                $taxInput !== '' ? ' · ' . $taxInput : '',
                $this->money($taxAmount),
            );
        }

        return $details;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => 'Produk',
            WorkItem::TYPE_SERVICE_ONLY => 'Servis',
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => 'Servis + Produk Toko',
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => 'Servis + Produk Luar',
            default => $type,
        };
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
