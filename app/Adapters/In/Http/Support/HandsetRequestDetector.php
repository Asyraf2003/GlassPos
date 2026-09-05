<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Support;

use Illuminate\Http\Request;

final class HandsetRequestDetector
{
    public function isHandset(Request $request): bool
    {
        $mobileHint = strtolower(trim((string) $request->headers->get('Sec-CH-UA-Mobile', '')));

        if ($mobileHint === '?1') {
            return true;
        }

        if ($mobileHint === '?0') {
            return false;
        }

        $userAgent = strtolower(trim((string) $request->userAgent()));

        if ($userAgent === '') {
            return false;
        }

        return str_contains($userAgent, 'iphone')
            || str_contains($userAgent, 'ipod')
            || str_contains($userAgent, 'windows phone')
            || str_contains($userAgent, 'opera mini')
            || (str_contains($userAgent, 'android') && str_contains($userAgent, 'mobile'));
    }
}
