<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Note\WorkItem\WorkItem;
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
        private readonly CurrentRevisionRowSettlementProjector $revisionSettlements,
        private readonly WorkItemOperationalStatusResolver $lineStatuses,
        private readonly CreateTransactionWorkspacePageDataBuilder $options,
        private readonly NoteWorkspacePanelDataBuilder $workspacePanel,
        private readonly NoteRefundPaymentOptionsBuilder $refundPaymentOptions,
        private readonly EditTransactionWorkspacePaymentSettlementDataBuilder $paymentSettlement,
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
        $packageLookupEndpoint = $this->urls->route($routeNames['packages_lookup']);
        $serviceLookupEndpoint = $this->urls->route($routeNames['services_lookup']);
        $serviceStoreEndpoint = $this->urls->route($routeNames['services_store']);

        $oldNote = [
            'customer_name' => $currentRevision->customerName(),
            'customer_phone' => $currentRevision->customerPhone() ?? '',
            'transaction_date' => $today,
        ];

        $currentLines = $currentRevision->lines();
        $lineSettlements = $this->revisionSettlements->build($note->id(), $currentLines);
        $oldItems = $this->revisionItems->mapLines(
            $this->editableRevisionLines($currentLines, $lineSettlements),
        );

        return [
            'pageTitle' => 'Edit Nota',
            'workspaceMode' => 'edit',
            'formAction' => $this->urls->route($routeNames['workspace_update'], ['noteId' => $normalized]),
            'cancelAction' => $this->urls->route($routeNames['show'], ['noteId' => $normalized]),
            'refundAction' => $this->urls->route($routeNames['refunds_store'], ['noteId' => $normalized]),
            'refundDateDefault' => $today,
            'refundPaymentOptions' => $this->refundPaymentOptions->build($note->id()),
            'workspacePaymentSettlement' => $this->paymentSettlement->build($note->id()),
            'workspaceRefundRows' => [],
            'canShowRefundModal' => false,
            'oldNote' => $oldNote,
            'oldItems' => $oldItems,
            'defaultCustomerName' => $oldNote['customer_name'],
            'productLookupEndpoint' => $productLookupEndpoint,
            'packageLookupEndpoint' => $packageLookupEndpoint,
            'serviceLookupEndpoint' => $serviceLookupEndpoint,
            'serviceStoreEndpoint' => $serviceStoreEndpoint,
            'draftLoadEndpoint' => $this->urls->route($routeNames['draft_show']),
            'draftSaveEndpoint' => $this->urls->route($routeNames['draft_save']),
            'workspaceConfigJson' => json_encode([
                'oldItems' => $oldItems,
                'defaultCustomerName' => $oldNote['customer_name'],
                'productLookupEndpoint' => $productLookupEndpoint,
                'packageLookupEndpoint' => $packageLookupEndpoint,
                'serviceLookupEndpoint' => $serviceLookupEndpoint,
                'serviceStoreEndpoint' => $serviceStoreEndpoint,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ] + $this->options->build();
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     * @param array<string, array<string, int|string>> $settlements
     * @return list<NoteRevisionLineSnapshot>
     */
    private function editableRevisionLines(array $lines, array $settlements): array
    {
        return array_values(array_filter(
            $lines,
            fn (NoteRevisionLineSnapshot $line): bool => $this->isEditableRevisionLine($line, $settlements),
        ));
    }

    /**
     * @param array<string, array<string, int|string>> $settlements
     */
    private function isEditableRevisionLine(NoteRevisionLineSnapshot $line, array $settlements): bool
    {
        if ($line->status() === WorkItem::STATUS_CANCELED) {
            return false;
        }

        $key = $line->workItemRootId() ?? $line->id();
        $settlement = $settlements[$key] ?? [];
        $refunded = (int) ($settlement['refunded_rupiah'] ?? 0);
        $outstanding = (int) ($settlement['outstanding_rupiah'] ?? $line->subtotalRupiah());

        return $this->lineStatuses->resolve($outstanding, $refunded) !== WorkItemOperationalStatusResolver::STATUS_REFUND;
    }
}
