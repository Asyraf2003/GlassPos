<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\AuditLog;

use App\Ports\Out\AuditLogReaderPort;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class AuditLogShowPageController extends Controller
{
    public function __invoke(string $source, string $auditId, AuditLogReaderPort $reader): View
    {
        $entry = $reader->findForAdmin($source, $auditId);
        abort_if($entry === null, 404);

        return view('admin.audit_logs.show', ['entry' => $entry]);
    }
}
