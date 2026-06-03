<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class CreateTransactionWorkspaceMobileUiLabPageController extends Controller
{
    public function __invoke(): View
    {
        return view('cashier.notes.workspace.mobile-ui-lab', [
            'pageTitle' => 'Lab UI Mobile Buat Nota',
            'activeVariant' => '01',
        ]);
    }
}
