<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreNoteRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $normalized = UpdateTransactionWorkspaceInputNormalizer::normalize($this->all());

        $reason = $this->input('reason');
        $defaultReason = $this->routeIs('admin.notes.*')
            ? 'Revisi workspace nota admin'
            : 'Revisi workspace nota kasir';

        $normalized['reason'] = is_string($reason) && trim($reason) !== ''
            ? trim($reason)
            : $defaultReason;

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return UpdateTransactionWorkspaceRules::build() + [
            'reason' => ['nullable', 'string', 'min:3', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            UpdateTransactionWorkspaceValidator::validate($this->all(), $validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.customer_name.required' => 'Nama customer wajib diisi.',
            'note.transaction_date.required' => 'Tanggal nota wajib diisi.',
            'items.required' => 'Minimal satu item wajib diisi.',
        ];
    }
}
