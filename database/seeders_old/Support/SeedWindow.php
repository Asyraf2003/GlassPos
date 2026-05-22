<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Carbon\CarbonImmutable;

final class SeedWindow
{
    /**
     * @return array{
     *   start: CarbonImmutable,
     *   end: CarbonImmutable,
     *   days: list<CarbonImmutable>
     * }
     */
    public static function baselineWeek(?CarbonImmutable $anchor = null): array
    {
        $end = ($anchor ?? CarbonImmutable::today('Asia/Jakarta'))->startOfDay();
        $start = $end->subDays(6);

        return [
            'start' => $start,
            'end' => $end,
            'days' => self::daysBetween($start, $end),
        ];
    }

    /**
     * @return array{
     *   start: CarbonImmutable,
     *   end: CarbonImmutable,
     *   days: list<CarbonImmutable>
     * }
     */
    public static function baselineMonth(?CarbonImmutable $anchor = null): array
    {
        $end = ($anchor ?? CarbonImmutable::today('Asia/Jakarta'))->startOfDay();
        $start = $end->subDays(29);

        return [
            'start' => $start,
            'end' => $end,
            'days' => self::daysBetween($start, $end),
        ];
    }

    /**
     * @return array{
     *   start: CarbonImmutable,
     *   end: CarbonImmutable,
     *   days: list<CarbonImmutable>
     * }
     */
    public static function loadYear(?CarbonImmutable $anchor = null): array
    {
        $end = ($anchor ?? CarbonImmutable::today('Asia/Jakarta'))->startOfDay();
        $start = $end->subDays(364);

        return [
            'start' => $start,
            'end' => $end,
            'days' => self::daysBetween($start, $end),
        ];
    }

    /**
     * @return list<CarbonImmutable>
     */
    public static function daysBetween(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = [];
        $cursor = $start->startOfDay();
        $finalDay = $end->startOfDay();

        while ($cursor->lessThanOrEqualTo($finalDay)) {
            $days[] = $cursor;
            $cursor = $cursor->addDay();
        }

        return $days;
    }
}
