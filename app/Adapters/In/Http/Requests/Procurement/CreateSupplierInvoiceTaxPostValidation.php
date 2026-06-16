<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use App\Application\Procurement\Services\SupplierInvoiceTaxLandedCostAllocator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

final class CreateSupplierInvoiceTaxPostValidation
{
    public function __construct(
        private readonly SupplierInvoiceTaxLandedCostAllocator $allocator,
    ) {
    }

    public function validate(FormRequest $request, Validator $validator): void
    {
        $lines = $request->input('lines');

        if (! is_array($lines) || $lines === []) {
            return;
        }

        foreach ($lines as $line) {
            if (! is_array($line)) {
                return;
            }

            if (! array_key_exists('qty_pcs', $line) || ! array_key_exists('line_total_rupiah', $line)) {
                return;
            }

            if (! is_numeric($line['qty_pcs']) || ! is_numeric($line['line_total_rupiah'])) {
                return;
            }
        }

        try {
            $this->allocator->allocate(array_values($lines), $request->input('tax_input'));
        } catch (InvalidArgumentException $exception) {
            $validator->errors()->add('tax_input', $exception->getMessage());
        }
    }
}
