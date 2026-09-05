<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Ports\Out\ClockPort;
use App\Support\ViewDateFormatter;
use DateTimeImmutable;
use DateTimeZone;
use Tests\TestCase;

final class ApplicationTimezoneConsistencyFeatureTest extends TestCase
{
    public function test_single_business_timezone_drives_php_clock_ui_and_database_session_configuration(): void
    {
        self::assertSame('Asia/Makassar', config('app.timezone'));
        self::assertSame(config('app.timezone'), config('app.display_timezone'));
        self::assertSame('Asia/Makassar', date_default_timezone_get());
        self::assertSame('+08:00', config('database.connections.mysql.timezone'));
        self::assertSame('+08:00', config('database.connections.mariadb.timezone'));

        $clockNow = app(ClockPort::class)->now();
        self::assertSame('Asia/Makassar', $clockNow->getTimezone()->getName());
        self::assertSame('Asia/Makassar', now()->getTimezone()->getName());

        $utcInstant = (new DateTimeImmutable(
            '2026-09-05 16:30:00',
            new DateTimeZone('UTC'),
        ))->getTimestamp();

        self::assertSame('2026-09-06', date('Y-m-d', $utcInstant));
        self::assertSame(
            '06 September 2026 00:30',
            ViewDateFormatter::display('2026-09-06 00:30:00', true),
        );
    }
}
