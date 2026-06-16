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

        if ($line->serviceLabel() !== null) {
            $details[] = sprintf(
                'Servis: %s%s',
                $line->serviceLabel(),
                $line->servicePriceRupiah() !== null
                    ? ' · ' . $this->money($line->servicePriceRupiah())
                    : ''
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
