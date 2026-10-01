<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\Services\SupplierInvoiceDuplicateNumberChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckSupplierInvoiceNumberController
{
    public function __invoke(Request $request, SupplierInvoiceDuplicateNumberChecker $checker): JsonResponse
    {
        $data = $request->validate(['nomor_faktur' => ['required', 'string']]);

        return response()->json([
            'success' => true,
            'data' => ['duplicate' => $checker->exists($data['nomor_faktur'])],
        ])->header('Cache-Control', 'no-store, private');
    }
}
