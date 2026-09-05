<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\AuditLog;

use App\Application\Audit\DTO\AuditLogTableQuery;
use App\Application\Audit\Services\AuditLogIndexPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;

final class AuditLogIndexPageController extends Controller
{
    public function __invoke(Request $request, AuditLogIndexPageData $pageData): View
    {
        $queryValue = $request->query('q', '');
        $search = is_string($queryValue) ? trim($queryValue) : '';

        $page = $pageData->tableForAdmin(AuditLogTableQuery::fromValidated([
            'q' => $search,
            'page' => $request->integer('page', 1),
            'sort_by' => $request->query('sort_by'),
            'sort_dir' => $request->query('sort_dir'),
            'source' => $request->query('source'),
        ]));

        $logs = new LengthAwarePaginator(
            $page->items,
            $page->total,
            $page->perPage,
            $page->currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('admin.audit_logs.index', [
            'logs' => $logs,
            'search' => $search,
            'source' => is_string($request->query('source')) ? $request->query('source') : '',
        ]);
    }
}
