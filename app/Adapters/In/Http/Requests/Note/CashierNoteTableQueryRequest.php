<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

final class CashierNoteTableQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->trimOrNull('date'),
            'search' => $this->trimOrNull('search'),
            'bucket' => $this->trimOrNull('bucket'),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string'],
            'bucket' => ['nullable', 'in:unfinished,completed'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
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
