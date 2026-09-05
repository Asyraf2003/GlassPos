<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ProcurementInvoiceTableQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->trimOrNull('q'),
            'payment_status' => $this->trimOrNull('payment_status'),
            'sort_by' => $this->trimOrNull('sort_by'),
            'sort_dir' => $this->trimOrNull('sort_dir'),
            'shipment_date_from' => $this->trimOrNull('shipment_date_from'),
            'shipment_date_to' => $this->trimOrNull('shipment_date_to'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'in:active,outstanding,paid,all,voided'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10'],
            'sort_by' => ['nullable', 'in:nomor_faktur,shipment_date,due_date,nama_pt_pengirim,grand_total_rupiah,total_paid_rupiah,outstanding_rupiah,receipt_count,total_received_qty'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'shipment_date_from' => ['nullable', 'date_format:Y-m-d'],
            'shipment_date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(fn (Validator $v) => $this->validateRanges($v));
    }

    private function validateRanges(Validator $validator): void
    {
        $from = $this->input('shipment_date_from');
        $to = $this->input('shipment_date_to');

        if ($from !== null && $to !== null && $from > $to) {
            $validator->errors()->add(
                'shipment_date_from',
                'Tanggal pengiriman mulai tidak boleh lebih besar dari tanggal pengiriman akhir.'
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
