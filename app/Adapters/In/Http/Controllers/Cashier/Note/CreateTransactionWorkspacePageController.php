<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Adapters\In\Http\Controllers\Cashier\Note\Support\CreateTransactionWorkspaceDraftPayloadLoader;
use App\Adapters\In\Http\Support\HandsetRequestDetector;
use App\Application\Note\Services\CreateTransactionWorkspacePageDataBuilder;
use App\Ports\Out\UuidPort;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CreateTransactionWorkspacePageController extends Controller
{
    public function __invoke(
        Request $request,
        CreateTransactionWorkspacePageDataBuilder $builder,
        CreateTransactionWorkspaceDraftPayloadLoader $draftPayloads,
        HandsetRequestDetector $devices,
        UuidPort $uuid,
    ): View {
        $page = $builder->build();
        $defaultCustomerName = (string) $page['defaultCustomerName'];
        $productLookupEndpoint = route('cashier.notes.products.lookup');
        $packageLookupEndpoint = route('cashier.notes.packages.lookup');
        $serviceLookupEndpoint = route('cashier.notes.services.lookup');
        $serviceStoreEndpoint = route('cashier.notes.services.store');
        $oldIdempotencyKey = $request->old('idempotency_key');
        $idempotencyKey = is_string($oldIdempotencyKey) && trim($oldIdempotencyKey) !== ''
            ? trim($oldIdempotencyKey)
            : $uuid->generate();

        $oldInput = $request->session()->get('_old_input', []);
        $sessionHasOldInput = is_array($oldInput) && $oldInput !== [];
        $draftPayload = $draftPayloads->load($request, $sessionHasOldInput);

        $oldNote = old('note');
        $oldItems = old('items');
        $oldInlinePayment = old('inline_payment');

        $noteFromDraft = is_array($draftPayload['note'] ?? null) ? $draftPayload['note'] : [];
        $itemsFromDraft = is_array($draftPayload['items'] ?? null) ? array_values($draftPayload['items']) : [];
        $inlinePaymentFromDraft = is_array($draftPayload['inline_payment'] ?? null) ? $draftPayload['inline_payment'] : [];

        $resolvedNote = is_array($oldNote) ? $oldNote : [
            'customer_name' => $noteFromDraft['customer_name'] ?? $defaultCustomerName,
            'customer_phone' => $noteFromDraft['customer_phone'] ?? '',
            'transaction_date' => $noteFromDraft['transaction_date'] ?? date('Y-m-d'),
        ];
        $resolvedItems = is_array($oldItems) ? array_values($oldItems) : $itemsFromDraft;
        $resolvedInlinePayment = is_array($oldInlinePayment) ? $oldInlinePayment : [
            'decision' => $inlinePaymentFromDraft['decision'] ?? 'skip',
            'payment_method' => $inlinePaymentFromDraft['payment_method'] ?? 'cash',
            'paid_at' => $inlinePaymentFromDraft['paid_at'] ?? date('Y-m-d'),
            'amount_paid_rupiah' => $inlinePaymentFromDraft['amount_paid_rupiah'] ?? '',
            'amount_received_rupiah' => $inlinePaymentFromDraft['amount_received_rupiah'] ?? '',
            'notes' => $inlinePaymentFromDraft['notes'] ?? '',
        ];

        $isHandset = $devices->isHandset($request);

        return view('cashier.notes.workspace.create', [
            'pageTitle' => 'Buat Nota',
            'oldNote' => $resolvedNote,
            'oldItems' => $resolvedItems,
            'oldInlinePayment' => $resolvedInlinePayment,
            'defaultCustomerName' => $defaultCustomerName,
            'productLookupEndpoint' => $productLookupEndpoint,
            'packageLookupEndpoint' => $packageLookupEndpoint,
            'serviceLookupEndpoint' => $serviceLookupEndpoint,
            'serviceStoreEndpoint' => $serviceStoreEndpoint,
            'idempotencyKey' => $idempotencyKey,
            'hasOldInput' => $sessionHasOldInput || $draftPayload !== [],
            'deviceClass' => $isHandset ? 'handset' : 'desktop',
            'presentationMode' => $isHandset ? 'simple' : 'detail',
        ] + $page);
    }
}
