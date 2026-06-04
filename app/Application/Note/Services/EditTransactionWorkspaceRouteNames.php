<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class EditTransactionWorkspaceRouteNames
{
    /**
     * @return array{
     *   workspace_update: string,
     *   show: string,
     *   refunds_store: string,
     *   products_lookup: string,
     *   services_lookup: string,
     *   services_store: string,
     *   draft_show: string,
     *   draft_save: string
     * }
     */
    public function resolve(string $routeArea): array
    {
        return match ($routeArea) {
            'admin' => [
                'workspace_update' => 'admin.notes.workspace.update',
                'show' => 'admin.notes.show',
                'refunds_store' => 'admin.notes.refunds.store',
                'products_lookup' => 'admin.notes.products.lookup',
                'services_lookup' => 'admin.notes.services.lookup',
                'services_store' => 'admin.notes.services.store',
                'draft_show' => 'admin.notes.workspace.draft.show',
                'draft_save' => 'admin.notes.workspace.draft.save',
            ],
            'cashier' => [
                'workspace_update' => 'cashier.notes.workspace.update',
                'show' => 'cashier.notes.show',
                'refunds_store' => 'cashier.notes.refunds.store',
                'products_lookup' => 'cashier.notes.products.lookup',
                'services_lookup' => 'cashier.notes.services.lookup',
                'services_store' => 'cashier.notes.services.store',
                'draft_show' => 'cashier.notes.workspace.draft.show',
                'draft_save' => 'cashier.notes.workspace.draft.save',
            ],
            default => throw new DomainException('Area route edit workspace tidak didukung.'),
        };
    }
}
