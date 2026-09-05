<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\ServiceProductTemplate;

use Illuminate\Foundation\Http\FormRequest;

final class ServiceProductTemplateTableQueryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->trimOrNull('q'),
            'status' => $this->trimOrNull('status') ?? 'all',
            'sort_by' => $this->trimOrNull('sort_by'),
            'sort_dir' => $this->trimOrNull('sort_dir'),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
            'sort_by' => ['nullable', 'in:product_name,service_name,default_service_price_rupiah,package_total,is_active'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    private function trimOrNull(string $key): ?string
    {
        $value = $this->input($key);
        if (! is_string($value)) return null;
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
