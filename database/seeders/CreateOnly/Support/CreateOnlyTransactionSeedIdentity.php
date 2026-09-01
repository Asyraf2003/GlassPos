<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

final class CreateOnlyTransactionSeedIdentity
{
    private const VERSION = 'v2';

    public static function key(string $profile, int $sequence): string
    {
        return sprintf(
            'seed-create-transaction-%s-%s-%s-%04d',
            trim($profile),
            self::VERSION,
            CreateOnlySeedCalendar::currentMonthPeriod(),
            $sequence,
        );
    }
}
