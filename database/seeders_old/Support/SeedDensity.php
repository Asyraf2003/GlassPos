<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

final class SeedDensity
{
    /**
     * @return array{
     *   notes_per_day:int,
     *   max_items_per_note:int,
     *   expense_rows_per_day:int,
     *   refund_notes_per_month:int,
     *   status_corrections_per_month:int,
     *   nominal_service_only_corrections_per_month:int,
     *   procurement_invoices_per_day:int,
     *   payment_distribution: array{
     *     full:int,
     *     partial:int,
     *     unpaid:int
     *   }
     * }
     */
    public static function baseline(): array
    {
        return [
            'notes_per_day' => 8,
            'max_items_per_note' => 4,
            'expense_rows_per_day' => 4,
            'refund_notes_per_month' => 12,
            'status_corrections_per_month' => 6,
            'nominal_service_only_corrections_per_month' => 6,
            'procurement_invoices_per_day' => 5,
            'payment_distribution' => [
                'full' => 60,
                'partial' => 25,
                'unpaid' => 15,
            ],
        ];
    }

    /**
     * @return array{
     *   notes_per_day:int,
     *   max_items_per_note:int,
     *   expense_rows_per_day:int,
     *   refund_paid_note_percent:int,
     *   correction_paid_note_percent:int,
     *   procurement_invoices_normal_per_day:int,
     *   procurement_invoices_spike_per_day:int,
     *   weekly_spike_days:int,
     *   weekly_spike_multiplier_percent:int,
     *   month_end_procurement_multiplier_percent:int,
     *   payment_distribution: array{
     *     full:int,
     *     partial:int,
     *     unpaid:int
     *   }
     * }
     */
    public static function monster(): array
    {
        return [
            'notes_per_day' => 25,
            'max_items_per_note' => 5,
            'expense_rows_per_day' => 10,
            'refund_paid_note_percent' => 5,
            'correction_paid_note_percent' => 5,
            'procurement_invoices_normal_per_day' => 8,
            'procurement_invoices_spike_per_day' => 10,
            'weekly_spike_days' => 3,
            'weekly_spike_multiplier_percent' => 175,
            'month_end_procurement_multiplier_percent' => 140,
            'payment_distribution' => [
                'full' => 60,
                'partial' => 25,
                'unpaid' => 15,
            ],
        ];
    }
}
