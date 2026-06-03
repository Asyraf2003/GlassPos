<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class CreateTransactionWorkspaceMobileUiLabPageController extends Controller
{
    /** @var array<string, string> */
    private const VARIANTS = [
        '01' => 'Google Form Classic',
        '02' => 'Stepper Nota Mobile',
        '03' => 'POS Cart Hybrid',
    ];

    public function __invoke(string $variant = '01'): View
    {
        $activeVariant = str_pad($variant, 2, '0', STR_PAD_LEFT);

        abort_unless(array_key_exists($activeVariant, self::VARIANTS), 404);

        return view('cashier.notes.workspace.mobile-ui-lab', [
            'pageTitle' => self::VARIANTS[$activeVariant],
            'activeVariant' => $activeVariant,
        ]);
    }
}
