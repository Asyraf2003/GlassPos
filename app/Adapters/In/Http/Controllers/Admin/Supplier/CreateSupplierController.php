<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Supplier;

use App\Adapters\In\Http\Requests\Procurement\UpdateSupplierRequest;
use App\Application\Procurement\UseCases\CreateSupplierHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CreateSupplierController extends Controller
{
    public function __invoke(UpdateSupplierRequest $request, CreateSupplierHandler $useCase): RedirectResponse
    {
        $useCase->handle(
            (string) $request->validated('nama_pt_pengirim'),
            $request->validated('bank_name'),
            $request->validated('bank_account_number'),
        );

        return redirect()->route('admin.suppliers.index')->with('success', 'Pemasok tersimpan.');
    }
}
