<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use App\Ports\Out\ClockPort;

final class OperationalProfitReportPdfViewDataBuilder
{
    public function __construct(
        private readonly ClockPort $clock,
    ) {
    }

    public function build(array $dataset, array $filters): array
    {
        $row = is_array($dataset['row'] ?? null) ? $dataset['row'] : [];

        $periodContext = ViewDateFormatter::reportPeriodContext(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        return [
            'title' => 'Ringkasan Kas Operasional',
            'periodLabelCaption' => $periodContext['label'],
            'periodLabel' => $periodContext['value'],
            'generatedAt' => $this->clock->now()->format('d/m/Y H:i'),
            'summaryItems' => $this->summaryItems($row),
        ];
    }

    private function summaryItems(array $row): array
    {
        return [
            ['label' => 'Uang Diterima', 'value' => $this->rupiah($row['cash_in_rupiah'] ?? 0)],
            ['label' => 'Uang Dikembalikan', 'value' => $this->rupiah($row['refunded_rupiah'] ?? 0)],
            ['label' => 'Biaya Barang Luar', 'value' => $this->rupiah($row['external_purchase_cost_rupiah'] ?? 0)],
            ['label' => 'Modal Barang Stok', 'value' => $this->rupiah($row['store_stock_cogs_rupiah'] ?? 0)],
            ['label' => 'Total Modal Produk', 'value' => $this->rupiah($row['product_purchase_cost_rupiah'] ?? 0)],
            ['label' => 'Biaya Operasional', 'value' => $this->rupiah($row['operational_expense_rupiah'] ?? 0)],
            ['label' => 'Gaji Dibayar', 'value' => $this->rupiah($row['payroll_disbursement_rupiah'] ?? 0)],
            ['label' => 'Kasbon/Hutang Karyawan', 'value' => $this->rupiah($row['employee_debt_cash_out_rupiah'] ?? 0)],
            ['label' => 'Sisa Kas Operasional', 'value' => $this->rupiah($row['cash_operational_profit_rupiah'] ?? 0)],
        ];
    }



    private function rupiah(mixed $value): string
    {
        return 'Rp '.number_format($this->integerValue($value), 0, ',', '.');
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }


}
