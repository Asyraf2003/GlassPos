<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class AdminNoteTableQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => $this->trimOrNull('date_from'),
            'date_to' => $this->trimOrNull('date_to'),
            'search' => $this->trimOrNull('search'),
            'line_status' => $this->trimOrNull('line_status'),
            'sort_by' => $this->trimOrNull('sort_by'),
            'sort_dir' => $this->trimOrNull('sort_dir'),
        ]);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string'],
            'line_status' => ['nullable', 'in:open,close,refund'],
            'sort_by' => ['nullable', 'in:created_at,total_rupiah,net_paid_rupiah,outstanding_rupiah'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
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
