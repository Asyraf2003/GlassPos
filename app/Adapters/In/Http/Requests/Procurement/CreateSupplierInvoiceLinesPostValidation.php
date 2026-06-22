<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateSupplierInvoiceLinesPostValidation
{
    public function validate(FormRequest $request, Validator $validator): void
    {
        $lines = $request->input('lines');

        if (! is_array($lines)) {
            return;
        }

        $this->validateDuplicateLineNo($lines, $validator);
        (new CreateSupplierInvoiceDuplicateProductPostValidation())->validate($lines, $validator);
        (new CreateSupplierInvoiceGrandTotalStorageLimitValidation())->validate($lines, $validator);
    }

    /**
     * @param array<int, mixed> $lines
     */
    private function validateDuplicateLineNo(array $lines, Validator $validator): void
    {
        $seen = [];

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $lineNo = $line['line_no'] ?? null;
            if ($lineNo === null || $lineNo === '') {
                continue;
            }

            $normalized = (string) $lineNo;

            if (array_key_exists($normalized, $seen)) {
                $validator->errors()->add(
                    'lines.' . $index . '.line_no',
                    'Nomor baris pada rincian tidak boleh duplikat.'
                );
            }

            $seen[$normalized] = true;
        }
    }

    /**
     * @param array<int, mixed> $lines
     */
    private function validateLineTotalDivisibleByQty(array $lines, Validator $validator): void
    {
        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $qty = isset($line['qty_pcs']) ? (int) $line['qty_pcs'] : 0;
            $lineTotal = isset($line['line_total_rupiah']) ? (int) $line['line_total_rupiah'] : 0;
            $lineNo = isset($line['line_no']) ? (int) $line['line_no'] : ((int) $index + 1);

            if ($qty < 1 || $lineTotal < 1 || $lineTotal % $qty === 0) {
                continue;
            }

            $validator->errors()->add(
                'lines.' . $index . '.line_total_rupiah',
                'Baris ' . $lineNo . ': total rincian harus habis dibagi qty.'
            );
        }
    }
}
