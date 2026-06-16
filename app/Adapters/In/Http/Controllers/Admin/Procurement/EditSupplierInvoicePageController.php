<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Adapters\In\Http\Controllers\Admin\Procurement\Support\EditSupplierInvoiceLineItemsViewBuilder;
use App\Application\Procurement\UseCases\GetProcurementInvoiceDetailHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class EditSupplierInvoicePageController extends Controller
{
    public function __construct(
        private readonly GetProcurementInvoiceDetailHandler $details,
        private readonly EditSupplierInvoiceLineItemsViewBuilder $lineItems,
    ) {
    }

    public function __invoke(string $supplierInvoiceId): View|RedirectResponse
    {
        $result = $this->details->handle($supplierInvoiceId);

        if ($result->isFailure()) {
            return redirect()
                ->route('admin.procurement.supplier-invoices.index')
                ->with('error', $result->message() ?? 'Nota supplier tidak ditemukan.');
        }

        $payload = $result->data();
        if (! is_array($payload)) {
            return redirect()
                ->route('admin.procurement.supplier-invoices.index')
                ->with('error', 'Detail nota supplier tidak valid.');
        }

        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

        if ((string) ($summary['policy_state'] ?? 'editable') === 'locked') {
            return redirect()->route('admin.procurement.supplier-invoices.revise', [
                'supplierInvoiceId' => $supplierInvoiceId,
            ]);
        }

        return view('admin.procurement.supplier_invoices.edit', [
            'summary' => $summary,
            'lines' => $lines,
            'formDefaults' => [
                'expected_revision_no' => old('expected_revision_no', (string) ($summary['last_revision_no'] ?? '1')),
                'change_reason' => old('change_reason', ''),
                'nomor_faktur' => old('nomor_faktur', (string) ($summary['nomor_faktur'] ?? '')),
                'nama_pt_pengirim' => old('nama_pt_pengirim', (string) ($summary['supplier_nama_pt_pengirim_snapshot'] ?? '')),
                'tanggal_pengiriman' => old('tanggal_pengiriman', (string) ($summary['shipment_date'] ?? '')),
                'tax_input' => old('tax_input', (string) ($summary['tax_input'] ?? '')),
            ],
            'lineItemsView' => $this->lineItems->build($lines),
            'returnRouteName' => 'admin.procurement.supplier-invoices.edit',
        ]);
    }
}
