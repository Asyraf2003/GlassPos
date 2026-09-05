<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier;

use App\Adapters\In\Http\Support\HandsetRequestDetector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CashierDashboardPageController extends Controller
{
    public function __invoke(Request $request, HandsetRequestDetector $devices): View
    {
        return view('cashier.dashboard.index', [
            'isHandset' => $devices->isHandset($request),
        ]);
    }
}
