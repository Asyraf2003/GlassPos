<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use Carbon\CarbonImmutable;

final class CreateOnlySeedCalendar
{
    public static function currentMonthPeriod(): string
    {
        return CarbonImmutable::now()->format('Y-m');
    }

    public static function currentMonthDate(int $day): string
    {
        return self::dateInMonth(CarbonImmutable::now()->startOfMonth(), $day);
    }

    public static function nextMonthDate(int $day): string
    {
        return self::dateInMonth(CarbonImmutable::now()->addMonthNoOverflow()->startOfMonth(), $day);
    }

    private static function dateInMonth(CarbonImmutable $monthStart, int $day): string
    {
        $safeDay = max(1, min($day, $monthStart->endOfMonth()->day));

        return $monthStart
            ->addDays($safeDay - 1)
            ->toDateString();
    }
}
