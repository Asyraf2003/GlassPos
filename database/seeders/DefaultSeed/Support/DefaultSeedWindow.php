<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed\Support;

use Carbon\CarbonImmutable;

final class DefaultSeedWindow
{
    public static function end(): CarbonImmutable
    {
        return CarbonImmutable::today((string) config('app.timezone', 'Asia/Makassar'));
    }

    public static function start(): CarbonImmutable
    {
        return self::end()->subMonthsNoOverflow(6);
    }

    public static function dateAt(int $index, int $total): CarbonImmutable
    {
        if ($total <= 1) {
            return self::start();
        }

        $start = self::start();
        $days = (int) $start->diffInDays(self::end());
        $offset = (int) floor(($days * $index) / ($total - 1));

        return $start->addDays($offset);
    }
}
