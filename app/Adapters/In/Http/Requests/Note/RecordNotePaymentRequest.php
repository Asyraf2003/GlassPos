<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class RecordNotePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            RecordNotePaymentInputNormalizer::normalize($this->all())
        );
    }

    public function rules(): array
    {
        return [
            'selected_row_ids' => ['nullable', 'array'],
            'selected_row_ids.*' => ['string', 'distinct'],
            'payment_scope' => ['nullable', 'string', 'in:partial'],
            'payment_method' => ['required', 'string', 'in:cash,transfer'],
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'amount_paid' => ['required_if:payment_scope,partial', 'nullable', 'integer', 'min:1'],
            'amount_received' => ['nullable', 'integer', 'min:1'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            RecordNotePaymentBusinessValidator::validate($this->all(), $validator);
        });
    }
}
