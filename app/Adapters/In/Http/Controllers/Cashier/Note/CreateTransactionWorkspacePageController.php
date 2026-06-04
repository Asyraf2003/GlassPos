<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\Note\Services\CreateTransactionWorkspacePageDataBuilder;
use App\Application\Note\Services\TransactionWorkspaceDraftData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CreateTransactionWorkspacePageController extends Controller
{
    public function __invoke(
        Request $request,
        CreateTransactionWorkspacePageDataBuilder $builder,
        TransactionWorkspaceDraftData $draftData,
    ): View {
        $page = $builder->build();
        $defaultCustomerName = (string) $page['defaultCustomerName'];
        $productLookupEndpoint = route('cashier.notes.products.lookup');
        $serviceLookupEndpoint = route('cashier.notes.services.lookup');
        $serviceStoreEndpoint = route('cashier.notes.services.store');

        $sessionHasOldInput = is_array($request->session()->get('_old_input', [])) && $request->session()->get('_old_input', []) !== [];
        $draftPayload = $this->loadDraftPayload($request, $draftData, $sessionHasOldInput);

        $oldNote = old('note');
        $oldItems = old('items');
        $oldInlinePayment = old('inline_payment');

        $noteFromDraft = is_array($draftPayload['note'] ?? null) ? $draftPayload['note'] : [];
        $itemsFromDraft = is_array($draftPayload['items'] ?? null) ? array_values($draftPayload['items']) : [];
        $inlinePaymentFromDraft = is_array($draftPayload['inline_payment'] ?? null) ? $draftPayload['inline_payment'] : [];

        $resolvedNote = is_array($oldNote) ? $oldNote : array_filter([
            'customer_name' => $noteFromDraft['customer_name'] ?? $defaultCustomerName,
            'customer_phone' => $noteFromDraft['customer_phone'] ?? '',
            'transaction_date' => $noteFromDraft['transaction_date'] ?? date('Y-m-d'),
        ], static fn ($value) => $value !== null);

        $resolvedItems = is_array($oldItems) ? array_values($oldItems) : $itemsFromDraft;

        $resolvedInlinePayment = is_array($oldInlinePayment) ? $oldInlinePayment : [
            'decision' => $inlinePaymentFromDraft['decision'] ?? 'skip',
            'payment_method' => $inlinePaymentFromDraft['payment_method'] ?? 'cash',
            'paid_at' => $inlinePaymentFromDraft['paid_at'] ?? date('Y-m-d'),
            'amount_paid_rupiah' => $inlinePaymentFromDraft['amount_paid_rupiah'] ?? '',
            'amount_received_rupiah' => $inlinePaymentFromDraft['amount_received_rupiah'] ?? '',
            'notes' => $inlinePaymentFromDraft['notes'] ?? '',
        ];

        return view('cashier.notes.workspace.create', [
            'pageTitle' => 'Buat Nota',
            'oldNote' => $resolvedNote,
            'oldItems' => $resolvedItems,
            'oldInlinePayment' => $resolvedInlinePayment,
            'defaultCustomerName' => $defaultCustomerName,
            'productLookupEndpoint' => $productLookupEndpoint,
            'serviceLookupEndpoint' => $serviceLookupEndpoint,
            'serviceStoreEndpoint' => $serviceStoreEndpoint,
            'hasOldInput' => $sessionHasOldInput || $draftPayload !== [],
        ] + $page);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDraftPayload(
        Request $request,
        TransactionWorkspaceDraftData $draftData,
        bool $sessionHasOldInput,
    ): array {
        if ($sessionHasOldInput) {
            return [];
        }

        $actorId = $request->user()?->getAuthIdentifier();

        if ($actorId === null) {
            return [];
        }

        $draft = $draftData->findByActorAndWorkspaceKey((string) $actorId, 'create');
        $payload = $draft['payload'] ?? null;

        return is_array($payload) ? $payload : [];
    }
}
