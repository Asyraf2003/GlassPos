<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\RouteUrlGeneratorPort;

final class EditTransactionWorkspacePageDataBuilder
{
    public function __construct(
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly NoteReaderPort $notes,
        private readonly NoteCurrentRevisionResolver $revisionResolver,
        private readonly NoteRevisionWorkspaceExistingItemMapper $revisionItems,
        private readonly CreateTransactionWorkspacePageDataBuilder $options,
        private readonly NoteWorkspacePanelDataBuilder $workspacePanel,
        private readonly NoteRefundPaymentOptionsBuilder $refundPaymentOptions,
        private readonly NoteOutstandingPaymentAmountResolver $paymentSettlement,
        private readonly RouteUrlGeneratorPort $urls,
        private readonly ClockPort $clock,
        private readonly EditTransactionWorkspaceRouteNames $routes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $noteId, string $routeArea = 'cashier'): array
    {
        $normalized = trim($noteId);

        $this->guard->assertWorkspaceEditPageAccessible($normalized);

        $note = $this->notes->getById($normalized);

        if ($note === null) {
            throw new DomainException('Nota tidak ditemukan.');
        }

        $currentRevision = $this->revisionResolver->resolveOrFail($normalized);

        $workspacePanel = $this->workspacePanel->build($normalized);

        if ($workspacePanel === null) {
            throw new DomainException('Panel workspace nota tidak ditemukan.');
        }

        $routeNames = $this->routes->resolve($routeArea);
        $today = $this->clock->now()->format('Y-m-d');
        $productLookupEndpoint = $this->urls->route($routeNames['products_lookup']);

        $oldNote = [
            'customer_name' => $currentRevision->customerName(),
            'customer_phone' => $currentRevision->customerPhone() ?? '',
            'transaction_date' => $today,
        ];

        $oldItems = $this->revisionItems->mapMany($currentRevision);

        return [
            'pageTitle' => 'Edit Nota',
            'workspaceMode' => 'edit',
            'formAction' => $this->urls->route($routeNames['workspace_update'], ['noteId' => $normalized]),
            'cancelAction' => $this->urls->route($routeNames['show'], ['noteId' => $normalized]),
            'refundAction' => $this->urls->route($routeNames['refunds_store'], ['noteId' => $normalized]),
            'refundDateDefault' => $today,
            'refundPaymentOptions' => $this->refundPaymentOptions->build($note->id()),
            'workspacePaymentSettlement' => $this->paymentSettlementData($note->id()),
            'workspaceRefundRows' => [],
            'canShowRefundModal' => false,
            'oldNote' => $oldNote,
            'oldItems' => $oldItems,
            'defaultCustomerName' => $oldNote['customer_name'],
            'productLookupEndpoint' => $productLookupEndpoint,
            'draftLoadEndpoint' => $this->urls->route($routeNames['draft_show']),
            'draftSaveEndpoint' => $this->urls->route($routeNames['draft_save']),
            'workspaceConfigJson' => json_encode([
                'oldItems' => $oldItems,
                'defaultCustomerName' => $oldNote['customer_name'],
                'productLookupEndpoint' => $productLookupEndpoint,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ] + $this->options->build();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentSettlementData(string $noteId): ?array
    {
        $result = $this->paymentSettlement->resolveFull($noteId);

        if ($result->isFailure()) {
            return null;
        }

        $data = $result->data();

        return is_array($data) ? $data : null;
    }
}
