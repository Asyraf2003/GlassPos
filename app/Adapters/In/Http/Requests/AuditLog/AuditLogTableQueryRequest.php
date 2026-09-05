<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\AuditLog;

use Illuminate\Foundation\Http\FormRequest;

final class AuditLogTableQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'sort_by', 'sort_dir', 'source'] as $key) {
            $value = $this->input($key);
            $this->merge([$key => is_string($value) && trim($value) !== '' ? trim($value) : null]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:20'],
            'sort_by' => ['nullable', 'in:created_at,event,source,actor,entity'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'source' => ['nullable', 'in:all,audit_logs,audit_events'],
        ];
    }
}
