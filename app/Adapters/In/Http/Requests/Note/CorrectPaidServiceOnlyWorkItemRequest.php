<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use Illuminate\Foundation\Http\FormRequest;

final class CorrectPaidServiceOnlyWorkItemRequest extends FormRequest
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
            'line_no' => ['required', 'integer', 'min:1'],
            'service_name' => ['required', 'string'],
            'service_price_rupiah' => ['required', 'integer', 'min:1'],
            'part_source' => ['required', 'string', 'in:' . implode(',', [
                ServiceDetail::PART_SOURCE_NONE,
                ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ])],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
