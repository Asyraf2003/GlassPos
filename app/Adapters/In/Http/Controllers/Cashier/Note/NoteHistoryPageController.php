<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NoteHistoryPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'search' => $this->resolveString($request, 'search') ?? '',
            'bucket' => $this->resolveString($request, 'bucket') ?? 'unfinished',
        ];

        return view('cashier.notes.index', [
            'pageTitle' => 'Riwayat Nota',
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
