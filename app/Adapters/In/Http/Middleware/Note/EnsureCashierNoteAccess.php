<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Middleware\Note;

use App\Application\Note\Services\CashierNoteRouteAccessData;
use App\Core\Shared\Exceptions\DomainException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCashierNoteAccess
{
    public function __construct(
        private readonly CashierNoteRouteAccessData $accessData,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $noteId = $request->route('noteId');

        if (! is_string($noteId) || trim($noteId) === '') {
            return $next($request);
        }

        try {
            if ($request->routeIs('cashier.notes.show') || $request->routeIs('cashier.notes.prototype.show')) {
                $canAccess = $this->accessData->ensureCanViewFinanceQueue($noteId);
            } elseif ($request->routeIs('cashier.notes.payments.store')) {
                $canAccess = $this->accessData->ensureCanCollectOutstanding($noteId);
            } elseif ($request->routeIs('cashier.notes.refunds.store')) {
                $canAccess = $this->accessData->ensureCanView($noteId);
            } elseif ($request->routeIs('cashier.notes.workspace.edit')) {
                $canAccess = $this->accessData->ensureCanOpenWorkspaceEdit($noteId);
            } else {
                $canAccess = $this->accessData->ensureCanMutateOpenNote($noteId);
            }
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        abort_if(! $canAccess, 404);

        return $next($request);
    }
}
