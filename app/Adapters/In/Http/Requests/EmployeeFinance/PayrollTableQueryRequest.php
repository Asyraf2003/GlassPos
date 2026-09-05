<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\EmployeeFinance;

use Illuminate\Foundation\Http\FormRequest;

final class PayrollTableQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->trimOrNull('q'),
            'sort_by' => $this->trimOrNull('sort_by'),
            'sort_dir' => $this->trimOrNull('sort_dir'),
            'mode' => $this->trimOrNull('mode'),
            'status' => $this->trimOrNull('status') ?? 'all',
            'date_from' => $this->trimOrNull('date_from'),
            'date_to' => $this->trimOrNull('date_to'),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
            'sort_by' => ['nullable', 'in:disbursement_date,employee_name,amount,mode,status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'mode' => ['nullable', 'in:daily,weekly,monthly'],
            'status' => ['nullable', 'in:all,active,reversed'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    private function trimOrNull(string $key): ?string
    {
        $value = $this->input($key);
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
