<?php

declare(strict_types=1);

namespace App\Adapters\Out\Clock;

use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use DateTimeZone;

final class SystemClockAdapter implements ClockPort
{
    public function now(): DateTimeImmutable
    {
        $configured = trim((string) config('app.timezone', 'Asia/Makassar'));
        $timezone = $configured !== '' ? $configured : 'Asia/Makassar';

        return new DateTimeImmutable('now', new DateTimeZone($timezone));
    }
}
