<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

use App\Application\Procurement\Services\SupplierInvoiceMetadataChange;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
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
        if ($request instanceof UpdateSupplierInvoiceRequest && $validator->errors()->isEmpty()) {
            $current = app(SupplierInvoiceReaderPort::class)->getById((string) $excludeSupplierInvoiceId);
            if ($current !== null && app(SupplierInvoiceMetadataChange::class)->matches(
                $current, (string) $request->input('nama_pt_pengirim'), (string) $request->input('tanggal_pengiriman'),
                $request->input('lines'), $request->input('tax_input'),
            )) {
                return;
            }
        }
        $this->taxValidation->validate($request, $validator);
    }
}
