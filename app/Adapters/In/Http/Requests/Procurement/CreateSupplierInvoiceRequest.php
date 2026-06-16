<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizer = new CreateSupplierInvoiceInputNormalizer();
        $this->merge($normalizer->normalize($this->all()));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nomor_faktur' => ['required', 'string'],
            'nama_pt_pengirim' => ['required', 'string'],
            'tanggal_pengiriman' => ['required', 'date_format:Y-m-d'],
            'auto_receive' => ['nullable', 'boolean'],
            'tanggal_terima' => ['nullable', 'date_format:Y-m-d'],
            'tax_input' => ['nullable', 'string', 'max:64'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_no' => ['required', 'integer', 'min:1'],
            'lines.*.product_id' => ['required', 'string'],
            'lines.*.qty_pcs' => ['required', 'integer', 'min:1'],
            'lines.*.line_total_rupiah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var CreateSupplierInvoicePostValidator $postValidator */
        $postValidator = app(CreateSupplierInvoicePostValidator::class);
        $validator->after(fn (Validator $v) => $postValidator->validate($this, $v));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_faktur.required' => 'Nomor faktur wajib diisi.',
            'nomor_faktur.string' => 'Nomor faktur harus berupa teks.',
            'nama_pt_pengirim.required' => 'Nama PT pengirim wajib diisi.',
            'nama_pt_pengirim.string' => 'Nama PT pengirim harus berupa teks.',
            'tanggal_pengiriman.required' => 'Tanggal pengiriman wajib diisi.',
            'tanggal_pengiriman.date_format' => 'Tanggal pengiriman harus menggunakan format YYYY-MM-DD.',
            'auto_receive.boolean' => 'Auto receive tidak valid.',
            'tanggal_terima.date_format' => 'Tanggal terima harus menggunakan format YYYY-MM-DD.',
            'tax_input.string' => 'Pajak supplier invoice harus berupa teks.',
            'tax_input.max' => 'Pajak supplier invoice maksimal 64 karakter.',
            'lines.required' => 'Rincian supplier invoice wajib diisi.',
            'lines.array' => 'Rincian supplier invoice tidak valid.',
            'lines.min' => 'Rincian supplier invoice minimal harus memiliki 1 baris.',
            'lines.*.line_no.required' => 'Nomor baris pada rincian wajib diisi.',
            'lines.*.line_no.integer' => 'Nomor baris pada rincian harus berupa bilangan bulat.',
            'lines.*.line_no.min' => 'Nomor baris pada rincian minimal 1.',
            'lines.*.product_id.required' => 'Produk pada rincian wajib dipilih.',
            'lines.*.product_id.string' => 'Produk pada rincian tidak valid.',
            'lines.*.qty_pcs.required' => 'Jumlah pada rincian wajib diisi.',
            'lines.*.qty_pcs.integer' => 'Jumlah pada rincian harus berupa bilangan bulat.',
            'lines.*.qty_pcs.min' => 'Jumlah pada rincian minimal 1.',
            'lines.*.line_total_rupiah.required' => 'Total rincian wajib diisi.',
            'lines.*.line_total_rupiah.integer' => 'Total rincian harus berupa bilangan bulat.',
            'lines.*.line_total_rupiah.min' => 'Total rincian minimal 1 rupiah.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nomor_faktur' => 'nomor faktur',
            'nama_pt_pengirim' => 'nama PT pengirim',
            'tanggal_pengiriman' => 'tanggal pengiriman',
            'auto_receive' => 'auto receive',
            'tanggal_terima' => 'tanggal terima',
            'tax_input' => 'pajak supplier invoice',
            'lines' => 'rincian supplier invoice',
            'lines.*.line_no' => 'nomor baris pada rincian',
            'lines.*.product_id' => 'produk pada rincian',
            'lines.*.qty_pcs' => 'jumlah pada rincian',
            'lines.*.line_total_rupiah' => 'total rincian',
        ];
    }
}
