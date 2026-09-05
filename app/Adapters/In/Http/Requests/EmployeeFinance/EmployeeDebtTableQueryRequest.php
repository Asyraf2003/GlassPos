<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\EmployeeFinance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeDebtTableQueryRequest extends FormRequest
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
            'status' => $this->trimOrNull('status') ?? 'all',
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
            'sort_by' => ['nullable', 'in:employee_name,latest_recorded_at,total_debt_records,total_debt_amount,total_remaining_balance,status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'status' => ['nullable', 'in:all,active,paid'],
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
