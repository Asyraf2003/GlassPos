<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class AddNoteRowsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizer = new CreateNoteRowInputNormalizer();

        $this->merge([
            'rows' => $normalizer->normalizeRows($this->input('rows')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'base_revision_id' => ['required', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.line_type' => ['required', 'string', 'in:product,service'],
            'rows.*.product_id' => ['nullable', 'string'],
            'rows.*.qty' => ['nullable', 'integer', 'min:1'],
            'rows.*.service_name' => ['nullable', 'string'],
            'rows.*.service_price_rupiah' => ['nullable', 'integer', 'min:1'],
            'rows.*.service_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $rowValidator = new CreateNoteRowValidator();
        $validator->after(fn (Validator $v) => $rowValidator->validate($this->input('rows', []), $v));
    }
}
