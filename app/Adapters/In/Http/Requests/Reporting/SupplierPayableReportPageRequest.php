<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SupplierPayableReportPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period_mode' => $this->trimOrNull('period_mode'),
            'reference_date' => $this->trimOrNull('reference_date'),
            'date_from' => $this->trimOrNull('date_from'),
            'date_to' => $this->trimOrNull('date_to'),
        ]);
    }

    public function rules(): array
    {
        return [
            'period_mode' => ['nullable', 'in:all,daily,weekly,monthly,custom'],
            'reference_date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn (Validator $v) => $this->validateFilters($v));
    }

    private function validateFilters(Validator $validator): void
    {
        $mode = $this->input('period_mode', 'all');
        $from = $this->input('date_from');
        $to = $this->input('date_to');

        if ($mode === 'custom' && $from === null) {
            $validator->errors()->add('date_from', 'Tanggal mulai wajib diisi untuk mode custom.');
        }

        if ($mode === 'custom' && $to === null) {
            $validator->errors()->add('date_to', 'Tanggal akhir wajib diisi untuk mode custom.');
        }

        if ($from !== null && $to !== null && (string) $from > (string) $to) {
            $validator->errors()->add(
                'date_from',
                'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.'
            );
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
