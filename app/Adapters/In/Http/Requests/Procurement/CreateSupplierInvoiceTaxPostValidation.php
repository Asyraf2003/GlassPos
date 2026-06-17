<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use App\Application\Procurement\Services\SupplierInvoiceTaxLandedCostAllocator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class CreateSupplierInvoiceTaxPostValidation
{
    public function __construct(
        private readonly SupplierInvoiceTaxLandedCostAllocator $allocator,
    ) {
    }

    public function validate(Request $request, Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $lines = $request->input('lines');

        if (! is_array($lines)) {
            return;
        }

        if ($this->hasHeaderTax($request->input('tax_input')) && $this->hasLineTax($lines)) {
            $validator->errors()->add(
                'tax_input',
                'Pilih salah satu: pajak supplier invoice atau pajak per rincian.'
            );

            return;
        }

        try {
            $this->allocator->allocate(array_values($lines), $request->input('tax_input'));
        } catch (InvalidArgumentException $exception) {
            $validator->errors()->add(
                $this->taxErrorAttribute($lines),
                $exception->getMessage()
            );
        }
    }

    /** @param array<int|string, mixed> $lines */
    private function hasLineTax(array $lines): bool
    {
        foreach ($lines as $line) {
            if (is_array($line) && $this->hasHeaderTax($line['tax_input'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function hasHeaderTax(mixed $value): bool
    {
        return trim((string) ($value ?? '')) !== '';
    }

    /** @param array<int|string, mixed> $lines */
    private function taxErrorAttribute(array $lines): string
    {
        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $value = $line['tax_input'] ?? null;

            if (trim((string) ($value ?? '')) !== '') {
                return 'lines.' . $index . '.tax_input';
            }
        }

        return 'tax_input';
    }
}
