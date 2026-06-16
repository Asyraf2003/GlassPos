<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Adapters\In\Http\Requests\Procurement\UpdateSupplierInvoiceRequest;
use App\Application\Procurement\UseCases\UpdateSupplierInvoiceHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class UpdateSupplierInvoiceController extends Controller
{
    public function __invoke(
        UpdateSupplierInvoiceRequest $request,
        UpdateSupplierInvoiceHandler $useCase,
        string $supplierInvoiceId,
    ): RedirectResponse {
        $data = $request->validated();

        $user = $request->user();
        $actorId = $user?->getAuthIdentifier();

        $result = $useCase->handle(
            $supplierInvoiceId,
            (string) $data['nomor_faktur'],
            (string) $data['nama_pt_pengirim'],
            (string) $data['tanggal_pengiriman'],
            $data['lines'],
            isset($data['expected_revision_no']) ? (int) $data['expected_revision_no'] : null,
            isset($data['change_reason']) ? (string) $data['change_reason'] : null,
            $actorId !== null ? (string) $actorId : null,
            null,
            'web_admin',
            taxInput: $this->resolveTaxInput($data),
        );

        if ($result->isFailure()) {
            $errors = $result->errors()['supplier_invoice'] ?? [];

            if ($errors === ['SUPPLIER_INVOICE_NOT_FOUND']) {
                return redirect()
                    ->route('admin.procurement.supplier-invoices.index')
                    ->with('error', $result->message() ?? 'Nota supplier tidak ditemukan.');
            }

            if ($errors === ['SUPPLIER_INVOICE_LOCKED']) {
                return redirect()
                    ->route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $supplierInvoiceId])
                    ->with('error', $result->message() ?? 'Nota supplier ini sudah terkunci. Gunakan correction / reversal.');
            }

            return back()
                ->withErrors([
                    'supplier_invoice' => $result->message() ?? 'Nota supplier gagal diperbarui.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $supplierInvoiceId])
            ->with('success', $result->message() ?? 'Nota supplier berhasil diperbarui.');
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

    }
}
