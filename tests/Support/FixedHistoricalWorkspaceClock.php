<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Ports\Out\ClockPort;
use DateTimeImmutable;

final class FixedHistoricalWorkspaceClock implements ClockPort
{
    public function __construct(private readonly string $now) {}

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->now);
    }
}
