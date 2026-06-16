<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateSupplierInvoicePostValidator
{
    public function __construct(
        private readonly CreateSupplierInvoiceDuplicateNumberPostValidation $duplicateNumberValidation,
        private readonly CreateSupplierInvoiceTaxPostValidation $taxValidation,
    ) {
    }

    public function validate(FormRequest $request, Validator $validator): void
    {
        $routeSupplierInvoiceId = $request->route('supplierInvoiceId');
        $excludeSupplierInvoiceId = is_string($routeSupplierInvoiceId)
            ? trim($routeSupplierInvoiceId)
            : null;

        $this->duplicateNumberValidation->validate(
            (string) $request->input('nomor_faktur', ''),
            $validator,
            $excludeSupplierInvoiceId !== '' ? $excludeSupplierInvoiceId : null,
        );

        (new CreateSupplierInvoiceDatePostValidation())->validate($request, $validator);
        (new CreateSupplierInvoiceLinesPostValidation())->validate($request, $validator);
        $this->taxValidation->validate($request, $validator);
    }
}
