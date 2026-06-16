<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizer = new CreateSupplierInvoiceInputNormalizer();
        $normalized = $normalizer->normalize($this->all());

        $normalized['change_reason'] = $this->normalizeNullableString($this->input('change_reason'));

        if (isset($normalized['lines']) && is_array($normalized['lines'])) {
            $normalized['lines'] = array_map(function ($line): array {
                if (! is_array($line)) {
                    return [];
                }

                $line['previous_line_id'] = $this->normalizeNullableString($line['previous_line_id'] ?? null);

                return $line;
            }, $normalized['lines']);
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'expected_revision_no' => ['required', 'integer', 'min:1'],
            'change_reason' => ['required', 'string'],
            'nomor_faktur' => ['required', 'string'],
            'nama_pt_pengirim' => ['required', 'string'],
            'tanggal_pengiriman' => ['required', 'date_format:Y-m-d'],
            'tax_input' => ['nullable', 'string', 'max:64'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_no' => ['required', 'integer', 'min:1'],
            'lines.*.previous_line_id' => ['nullable', 'string'],
            'lines.*.product_id' => ['required', 'string'],
            'lines.*.qty_pcs' => ['required', 'integer', 'min:1'],
            'lines.*.line_total_rupiah' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expected_revision_no.required' => 'Revision faktur wajib dikirim.',
            'expected_revision_no.integer' => 'Revision faktur harus berupa angka.',
            'expected_revision_no.min' => 'Revision faktur minimal 1.',
            'change_reason.required' => 'Alasan perubahan wajib diisi.',
            'tax_input.string' => 'Pajak supplier invoice harus berupa teks.',
            'tax_input.max' => 'Pajak supplier invoice maksimal 64 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        /** @var CreateSupplierInvoicePostValidator $postValidator */
        $postValidator = app(CreateSupplierInvoicePostValidator::class);
        $validator->after(fn (Validator $v) => $postValidator->validate($this, $v));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
