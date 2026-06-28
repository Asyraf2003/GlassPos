<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\ProductCatalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_barang' => $this->normalizeNullableString($this->input('kode_barang')),
            'change_reason' => $this->normalizeNullableString($this->input('change_reason')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $productId = $this->route('productId');
        $changeReasonPresence = $this->routeIs('admin.products.update') ? 'required' : 'nullable';

        return [
            'kode_barang' => [
                'nullable',
                'string',
                Rule::unique('products', 'kode_barang')
                    ->ignore($productId, 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'nama_barang' => ['required', 'string'],
            'merek' => ['required', 'string'],
            'ukuran' => ['nullable', 'integer'],
            'harga_jual' => ['required', 'integer', 'min:1'],
            'reorder_point_qty' => ['nullable', 'integer', 'min:0'],
            'critical_threshold_qty' => ['nullable', 'integer', 'min:0'],
            'change_reason' => [$changeReasonPresence, 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_barang.unique' => 'Kode barang sudah dipakai product lain.',
            'change_reason.required' => 'Catatan perubahan wajib diisi.',
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
