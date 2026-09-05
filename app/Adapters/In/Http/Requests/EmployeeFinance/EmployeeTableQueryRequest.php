<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\EmployeeFinance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeTableQueryRequest extends FormRequest
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
            'employment_status' => $this->trimOrNull('employment_status'),
            'salary_basis_type' => $this->trimOrNull('salary_basis_type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
            'sort_by' => ['nullable', 'in:employee_name,phone,default_salary_amount,salary_basis_type,employment_status'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'employment_status' => ['nullable', 'in:active,inactive'],
            'salary_basis_type' => ['nullable', 'in:daily,weekly,monthly,manual'],
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
