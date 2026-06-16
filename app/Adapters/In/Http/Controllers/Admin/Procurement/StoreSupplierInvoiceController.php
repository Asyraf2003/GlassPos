<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Adapters\In\Http\Requests\Procurement\CreateSupplierInvoiceRequest;
use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class StoreSupplierInvoiceController extends Controller
{
    public function __invoke(
        CreateSupplierInvoiceRequest $request,
        CreateSupplierInvoiceFlowHandler $useCase,
    ): RedirectResponse {
        $data = $request->validated();

        $result = $useCase->handle(
            (string) $data['nomor_faktur'],
            (string) $data['nama_pt_pengirim'],
            (string) $data['tanggal_pengiriman'],
            $data['lines'],
            $this->resolveAutoReceive($data),
            $this->resolveTanggalTerima($data),
            taxInput: $this->resolveTaxInput($data),
        );

        if ($result->isFailure()) {
            return back()
                ->withErrors([
                    'supplier_invoice' => $result->message() ?? 'Nota supplier gagal dibuat.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('admin.procurement.supplier-invoices.index')
            ->with('success', $result->message() ?? 'Nota supplier berhasil dibuat.')
            ->with('clear_procurement_create_draft', true);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveTaxInput(array $data): ?string
    {
        if (! array_key_exists('tax_input', $data) || $data['tax_input'] === null) {
            return null;
        }

        $value = trim((string) $data['tax_input']);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveAutoReceive(array $data): bool
    {
        if (! array_key_exists('auto_receive', $data) || $data['auto_receive'] === null) {
            return true;
        }

        return (bool) $data['auto_receive'];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveTanggalTerima(array $data): ?string
    {
        if (! array_key_exists('tanggal_terima', $data) || $data['tanggal_terima'] === null) {
            return null;
        }

        return (string) $data['tanggal_terima'];
    }
}
