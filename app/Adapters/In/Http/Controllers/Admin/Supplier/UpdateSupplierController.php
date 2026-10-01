<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Supplier;

use App\Adapters\In\Http\Requests\Procurement\UpdateSupplierRequest;
use App\Application\Procurement\UseCases\UpdateSupplierHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class UpdateSupplierController extends Controller
{
    public function __invoke(
        UpdateSupplierRequest $request,
        UpdateSupplierHandler $useCase,
        string $supplierId,
    ): RedirectResponse {
        $result = $useCase->handle(
            $supplierId,
            (string) $request->validated('nama_pt_pengirim'),
            $request->safe()->only(['bank_name', 'bank_account_number']),
        );

        if ($result->isFailure()) {
            if (($result->errors()['supplier'] ?? []) === ['SUPPLIER_NOT_FOUND']) {
                return redirect()
                    ->route('admin.suppliers.index')
                    ->with('error', $result->message() ?? 'Supplier tidak ditemukan.');
            }

            return back()
                ->withErrors([
                    'supplier' => $result->message() ?? 'Supplier gagal diperbarui.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', $result->message() ?? 'Supplier berhasil diperbarui.');
    }
}
