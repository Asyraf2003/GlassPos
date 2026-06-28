<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Http\FormRequest;

final class CorrectPaidWorkItemStatusRequest extends FormRequest
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
            'target_status' => ['required', 'string', 'in:' . implode(',', [
                WorkItem::STATUS_OPEN,
                WorkItem::STATUS_DONE,
                WorkItem::STATUS_CANCELED,
            ])],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
