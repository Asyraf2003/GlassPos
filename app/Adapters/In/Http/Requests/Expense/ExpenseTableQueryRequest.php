<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ExpenseTableQueryRequest extends FormRequest
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
            'category_id' => $this->trimOrNull('category_id'),
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
            'sort_by' => ['nullable', 'in:expense_date,category_name_snapshot,description,amount_rupiah,payment_method'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'category_id' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn (Validator $v) => $this->validateDateRange($v));
    }

    private function validateDateRange(Validator $validator): void
    {
        $from = $this->input('date_from');
        $to = $this->input('date_to');

        if ($from !== null && $to !== null && (string) $from > (string) $to) {
            $validator->errors()->add('date_from', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
        }
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
