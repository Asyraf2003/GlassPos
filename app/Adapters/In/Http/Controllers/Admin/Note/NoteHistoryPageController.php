<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Note;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NoteHistoryPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $today = date('Y-m-d');
        $search = $this->resolveString($request, 'search') ?? '';

        $filters = [
            'date_from' => $this->resolveString($request, 'date_from') ?? $today,
            'date_to' => $this->resolveString($request, 'date_to') ?? $today,
            'search' => $search,
            'line_status' => $this->resolveString($request, 'line_status') ?? '',
            'sort_by' => $this->resolveString($request, 'sort_by') ?? ($search !== '' ? 'relevance' : 'created_at'),
            'sort_dir' => $this->resolveString($request, 'sort_dir') ?? ($search !== '' ? 'asc' : 'desc'),
        ];

        return view('admin.notes.index', [
            'pageTitle' => 'Daftar Nota',
            'filters' => $filters,
        ]);
    }

    private function resolveString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
