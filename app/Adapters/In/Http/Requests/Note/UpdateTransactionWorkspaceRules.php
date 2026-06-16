<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class UpdateTransactionWorkspaceRules
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function build(): array
    {
        return [
            'note' => ['required', 'array'],
            'note.customer_name' => ['required', 'string'],
            'note.customer_phone' => ['nullable', 'string'],
            'note.transaction_date' => ['required', 'date_format:Y-m-d'],
            'note.operational_note' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.entry_mode' => ['required', 'string', 'in:product,service'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.part_source' => ['nullable', 'string', 'in:none,store_stock,customer_owned,external_purchase'],
            'items.*.pricing_mode' => ['nullable', 'string', 'in:manual_split,package_auto_split'],
            'items.*.package_total_rupiah' => ['nullable', 'integer', 'min:1'],

            'items.*.service' => ['nullable', 'array'],
            'items.*.service.name' => ['nullable', 'string'],
            'items.*.service.price_rupiah' => ['nullable', 'integer', 'min:0'],
            'items.*.service.notes' => ['nullable', 'string'],

            'items.*.product_lines' => ['nullable', 'array'],
            'items.*.product_lines.*.product_id' => ['nullable', 'string'],
            'items.*.product_lines.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.product_lines.*.unit_price_rupiah' => ['nullable', 'integer', 'min:1'],
            'items.*.product_lines.*.price_basis' => ['nullable', 'string', 'in:current_catalog,revision_snapshot'],

            'items.*.external_purchase_lines' => ['nullable', 'array'],
            'items.*.external_purchase_lines.0.label' => ['nullable', 'string'],
            'items.*.external_purchase_lines.0.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.external_purchase_lines.0.unit_cost_rupiah' => ['nullable', 'integer', 'min:1'],
            'items.*.external_purchase_lines.*.total_rupiah' => ['nullable', 'integer', 'min:1'],

            'inline_payment' => ['required', 'array'],
            'inline_payment.decision' => ['required', 'string', 'in:skip,pay_full,pay_partial'],
            'inline_payment.payment_method' => ['nullable', 'string', 'in:cash,transfer'],
            'inline_payment.paid_at' => ['nullable', 'date_format:Y-m-d'],
            'inline_payment.amount_paid_rupiah' => ['nullable', 'integer', 'min:1'],
            'inline_payment.amount_received_rupiah' => ['nullable', 'integer', 'min:1'],
            'inline_payment.notes' => ['nullable', 'string'],
        ];
    }
}
