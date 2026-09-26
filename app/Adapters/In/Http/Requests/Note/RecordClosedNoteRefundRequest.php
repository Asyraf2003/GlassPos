<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

final class RecordClosedNoteRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'selected_row_ids' => ['required', 'array', 'min:1'],
            'selected_row_ids.*' => ['string', 'distinct'],
            'stock_returns' => ['sometimes', 'array'],
            'stock_returns.*' => ['required', 'boolean'],
            'refunded_at' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }
}
