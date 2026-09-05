<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\In\Http\Support;

use App\Adapters\In\Http\Support\HandsetRequestDetector;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class HandsetRequestDetectorTest extends TestCase
{
    public function test_mobile_client_hint_is_handset(): void
    {
        $request = $this->request('Mozilla/5.0 X11 Linux x86_64', '?1');

        self::assertTrue((new HandsetRequestDetector())->isHandset($request));
    }

    public function test_desktop_client_hint_stays_desktop_even_with_small_viewport_concept(): void
    {
        $request = $this->request('Mozilla/5.0 X11 Linux x86_64', '?0');

        self::assertFalse((new HandsetRequestDetector())->isHandset($request));
    }

    public function test_iphone_user_agent_is_handset_when_client_hint_is_missing(): void
    {
        $request = $this->request('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile/15E148 Safari/604.1');

        self::assertTrue((new HandsetRequestDetector())->isHandset($request));
    }

    public function test_desktop_user_agent_is_not_handset_when_client_hint_is_missing(): void
    {
        $request = $this->request('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/151.0 Safari/537.36');

        self::assertFalse((new HandsetRequestDetector())->isHandset($request));
    }

    private function request(string $userAgent, ?string $mobileHint = null): Request
    {
        $server = ['HTTP_USER_AGENT' => $userAgent];

        if ($mobileHint !== null) {
            $server['HTTP_SEC_CH_UA_MOBILE'] = $mobileHint;
        }

        return Request::create('/admin/dashboard', 'GET', [], [], [], $server);
    }
}
